<?php

namespace App\Http\Controllers;

use App\Models\Embedding;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

use App\Models\Destino; // Requerido para obtener la metadata P
use App\Http\Controllers\ContextoController; // Requerido para obtener el Contexto C
use Illuminate\Support\Facades\Log; // Recomendado para depuración

class EmbeddingController extends Controller
{
    // ==============================================================================
    // FUNCIONES AUXILIARES MATEMÁTICAS (Núcleo de la Similitud de Coseno)
    // ==============================================================================

    /**
     * Normaliza un vector para que su magnitud L2 sea 1.
     * @param array $vector
     * @return array
     */
    protected function normalizeVector(array $vector): array
    {
        $magnitude = $this->magnitude($vector);
        if ($magnitude == 0.0) {
            return $vector;
        }

        $normalizedVector = [];
        foreach ($vector as $value) {
            $normalizedVector[] = $value / $magnitude;
        }
        return $normalizedVector;
    }

    /**
     * Calcula el producto punto (dot product) de dos vectores.
     * @param array $vectorA
     * @param array $vectorB
     * @return float
     */
    protected function dotProduct(array $vectorA, array $vectorB): float
    {
        if (count($vectorA) !== count($vectorB)) {
            // En un ambiente real, esto debe manejarse mejor, pero para la simulación de GNN, asumimos vectores de misma dimensión.
            throw new Exception("Los vectores deben tener la misma dimensión para el producto punto.");
        }

        $product = 0.0;
        foreach ($vectorA as $index => $value) {
            $product += $value * $vectorB[$index];
        }
        return $product;
    }

    /**
     * Calcula la magnitud (norma L2) de un vector.
     * @param array $vector
     * @return float
     */
    protected function magnitude(array $vector): float
    {
        $sumOfSquares = 0.0;
        foreach ($vector as $value) {
            $sumOfSquares += $value * $value;
        }
        return sqrt($sumOfSquares);
    }

    /**
     * Calcula la similitud de coseno entre dos vectores.
     * @param array $vectorA
     * @param array $vectorB
     * @return float
     */
    protected function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        try {
            $dotProduct = $this->dotProduct($vectorA, $vectorB);
            $magnitudeA = $this->magnitude($vectorA);
            $magnitudeB = $this->magnitude($vectorB);
        } catch (Exception $e) {
            // Si la dimensión no coincide, devolvemos 0
            return 0.0;
        }


        // Previene la división por cero si uno o ambos vectores son el vector cero (magnitud 0).
        if ($magnitudeA == 0.0 || $magnitudeB == 0.0) {
            return 0.0;
        }

        // Asegura que el valor esté entre -1 y 1 (evita errores de precisión flotante)
        $similarity = $dotProduct / ($magnitudeA * $magnitudeB);
        return max(-1.0, min(1.0, $similarity));
    }

    // ==============================================================================
    // B2.1.1: Obtener Embedding Inicial (U₀)
    // ==============================================================================

    /**
     * Display the initial embedding (U₀) for a specific user.
     * GET /api/usuario/{id_usuario}/embedding/initial
     */
    public function getInitialUserEmbedding(string $id_usuario)
    {
        try {
            $embedding = Embedding::where('tipo_nodo', 'U')
                ->where('id_referencia', $id_usuario)
                ->orderBy('created_at', 'asc')
                ->firstOrFail();

            $vector = json_decode($embedding->vector_embedding, true);

            return response()->json([
                'id_usuario' => (int) $id_usuario,
                'embedding_vector' => $vector,
                'embedding_version' => 0,
                'created_at' => $embedding->created_at,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Embedding inicial (U₀) para el usuario ' . $id_usuario . ' no encontrado.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener el embedding inicial.', 'message' => $e->getMessage()], 500);
        }
    }

    // ==============================================================================
    // B2.1.2: Lógica de Similitud (Recomendación)
    // ==============================================================================

    /**
     * Calculates the cosine similarity between the user's initial embedding (U₀)
     * and all other P, C, or E embeddings, returning the top 3.
     * GET /api/usuario/{id_usuario}/recommendations
     */
    public function calculateSimilarity(string $id_usuario)
    {
        // Llama a la lógica principal de propagación, pero establece 'false' para NO guardar el nuevo U₁
        $response = $this->propagateAndAggregate($id_usuario, false);

        // La respuesta ya está formateada para incluir las recomendaciones
        if ($response instanceof \Illuminate\Http\JsonResponse && $response->getStatusCode() == 200) {
            $content = json_decode($response->getContent(), true);

            // Si solo pedimos la similitud, quitamos los datos de propagación y guardado
            unset($content['nuevo_embedding_id']);
            unset($content['nuevo_embedding_U1']);

            return response()->json($content, 200);
        }

        return $response; // Retorna la respuesta de error o éxito de la función principal
    }

    // App\Http\Controllers\EmbeddingController.php

    // ==============================================================================
    // B2.2.1: Propagación y Agregación de Embeddings (Generación de U₁)
    // ==============================================================================

    /**
     * Simula la propagación GNN: Agrega los embeddings de los 3 nodos más similares
     * Y CONTEXTUALMENTE COMPATIBLES para generar un nuevo embedding de usuario (U₁).
     * POST /api/usuario/{id_usuario}/propagate
     */
    public function propagateAndAggregate(string $id_usuario, bool $save_new_embedding = true)
    {
        try {
            // 1. Obtener el Embedding Inicial del Usuario ($U_n$)
            $userEmbeddingRecord = Embedding::where('tipo_nodo', 'U')
                ->where('id_referencia', $id_usuario)
                ->orderBy('created_at', 'desc')
                ->firstOrFail();

            $userVector = json_decode($userEmbeddingRecord->vector_embedding, true);
            $vectorDimension = count($userVector);

            if (empty($userVector)) {
                return response()->json(['error' => 'El vector de embedding del usuario está vacío o es inválido.'], 500);
            }

            // 2. Obtener los Embeddings de los Nodos Candidatos (P, C, E)
            $candidateEmbeddings = Embedding::whereIn('tipo_nodo', ['P', 'C', 'E'])->get();

            $similarityResults = [];

            // 3. Calcular la Similitud de Coseno para cada candidato (B2.1.2: CÁLCULO BRUTO)
            foreach ($candidateEmbeddings as $candidate) {
                if (empty($candidate->vector_embedding)) continue;

                $candidateVector = json_decode($candidate->vector_embedding, true);

                if (count($candidateVector) === $vectorDimension) {
                    $similarity = $this->cosineSimilarity($userVector, $candidateVector);

                    $similarityResults[] = [
                        'tipo_nodo' => $candidate->tipo_nodo,
                        'id_referencia' => (int) $candidate->id_referencia,
                        'similitud_coseno' => (float) $similarity,
                        'id_embedding' => (int) $candidate->id_embedding,
                        'vector' => $candidateVector, // Incluir el vector para la agregación
                    ];
                }
            }

            // 4. Procesamiento de Recomendaciones: Ordenar, Filtrar y Seleccionar Top-N

            // 4a. Ordenar la Similitud (pre-filtrado)
            usort($similarityResults, function ($a, $b) {
                return $b['similitud_coseno'] <=> $a['similitud_coseno'];
            });

            // --- FILTRADO CONTEXTUAL (B2.1.3) ---

            // Obtener el Contexto Actual (C)
            // Se asume que ContextoController está importado
            $contextoController = new ContextoController();
            $contextoResponse = $contextoController->obtenerContextoActual(new Request());
            $contextoActual = json_decode($contextoResponse->getContent(), true);

            // Aplicación del filtro B2.1.3: Sólo si el contexto es válido
            if (isset($contextoActual['error']) || !isset($contextoActual['clima_actual'])) {
                $filteredResults = $similarityResults;
                Log::warning('No se pudo obtener el contexto actual o es inválido, saltando el filtrado B2.1.3.');
            } else {
                // Aplicar el Filtrado Contextual a toda la lista ordenada
                $filteredResults = $this->filtrarPorContexto($similarityResults, $contextoActual);
            }

            // --- FIN DEL FILTRADO ---

            // 4b. ⭐ IMPLEMENTACIÓN B2.1.4: Generar Ranking Inicial de 5 Destinos (P)
            $rankingDestinos = [];
            foreach ($filteredResults as $rec) {
                // SOLO se consideran nodos de Destino (P) para el ranking de sugerencias
                if ($rec['tipo_nodo'] === 'P') {
                    $rankingDestinos[] = $rec;
                }
                // Detenerse después de encontrar los 5 mejores destinos P
                if (count($rankingDestinos) >= 5) {
                    break;
                }
            }
            $topRanking5 = $rankingDestinos;


            // 4c. OBTENER TOP-3 para Propagación GNN (B2.2.1)
            // Tomamos los 3 nodos más similares (P, C, E) de la lista filtrada para la agregación GNN.
            $topRecommendationsGNN = array_slice($filteredResults, 0, 3);


            // 5. Función de Agregación (Generación de U₁)
            $aggregatedVector = array_fill(0, $vectorDimension, 0.0);
            $totalVectors = 0;

            // Incluir el vector inicial ($U_n$)
            $currentVector = $userVector;
            for ($i = 0; $i < $vectorDimension; $i++) {
                $aggregatedVector[$i] += $currentVector[$i];
            }
            $totalVectors++;

            // Incluir los vectores de las recomendaciones Top-3 GNN ($E_{top3}$)
            foreach ($topRecommendationsGNN as $rec) {
                for ($i = 0; $i < $vectorDimension; $i++) {
                    $aggregatedVector[$i] += $rec['vector'][$i];
                }
                $totalVectors++;
            }

            // Calcular el promedio
            $meanVector = ($totalVectors > 0)
                ? array_map(fn($val) => $val / $totalVectors, $aggregatedVector)
                : $userVector;


            // 6. Refinamiento (Normalización para obtener U_nuevo)
            $newVectorU1 = $this->normalizeVector($meanVector);

            $newEmbedding = null;
            $newEmbeddingId = null;

            if ($save_new_embedding) {
                // 7. Guardar el nuevo Embedding (U₁)
                $newEmbedding = Embedding::create([
                    'tipo_nodo' => 'U',
                    'id_referencia' => $id_usuario,
                    'vector_embedding' => json_encode($newVectorU1),
                    'fecha_generacion' => now(), // Guardar la fecha actual
                ]);
                $newEmbeddingId = $newEmbedding->id_embedding;
            }

            // 8. Devolver la respuesta formateada

            // Limpiar los vectores de la respuesta de Nodos GNN (Top-3)
            $recommendationsGNN = array_map(function ($rec) {
                unset($rec['vector']);
                return $rec;
            }, $topRecommendationsGNN);

            // Limpiar los vectores de la respuesta del Ranking de 5 (B2.1.4)

            $ranking5Only = array_map(function ($rec) {
                unset($rec['vector']);
                return $rec;
            }, $topRanking5);

            // 🚀 APLICACIÓN DE ENRIQUECIMIENTO (B2.1.4 final)
            $ranking_top_5_sugerencias_enriquecido = $this->enriquecerSugerencias($ranking5Only);

            return response()->json([
                'id_usuario' => (int) $id_usuario,
                'embedding_id_inicial' => (int) $userEmbeddingRecord->id_embedding,
                // Sustituimos $ranking5Only (solo con ID y Similitud) por la versión enriquecida
                'ranking_top_5_sugerencias' => $ranking_top_5_sugerencias_enriquecido, // <--- LISTO PARA EL FRONTEND
                'nodos_propagados_GNN' => $recommendationsGNN, // Los 3 nodos que definieron U₁
                'nuevo_embedding_id' => $newEmbeddingId,
                'nuevo_embedding_U1' => $newEmbeddingId ? $newVectorU1 : 'No guardado (solo similitud)',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Embedding de usuario (' . $id_usuario . ') no encontrado. No se puede propagar la recomendación.'], 404);
        } catch (Exception $e) {
            Log::error("Error en propagateAndAggregate para usuario $id_usuario: " . $e->getMessage());
            return response()->json(['error' => 'Error en la propagación y agregación GNN.', 'message' => $e->getMessage()], 500);
        }
    }

    // ==============================================================================
    // B2.1.3: LÓGICA DE FILTRADO CONTEXTUAL (C)
    // ==============================================================================

    /**
     * Función auxiliar para simplificar el clima complejo a una etiqueta de búsqueda.
     */
    protected function simplificarClima(string $clima_actual): string
    {
        $clima_actual = strtolower($clima_actual);

        if (str_contains($clima_actual, 'lluvioso') || str_contains($clima_actual, 'tormenta')) {
            return 'Lluvioso';
        }
        if (str_contains($clima_actual, 'soleado') || str_contains($clima_actual, 'despejado')) {
            return 'Soleado';
        }
        if (str_contains($clima_actual, 'nublado') || str_contains($clima_actual, 'templado')) {
            return 'Templado/Nublado';
        }
        // ¡CORRECCIÓN! Si es un clima no cubierto, asumimos que no es un factor de bloqueo.
        return ''; // Devolver cadena vacía si no se puede simplificar
    }
    /**
     * Implementa la lógica de filtrado de destinos por compatibilidad contextual.
     */
    protected function filtrarPorContexto(array $recommendations, array $contextoActual): array
    {
        $resultados_filtrados = [];

        // ⭐ OBTENCIÓN DE CONTEXTO CLAVE:
        $clima_simple = $this->simplificarClima($contextoActual['clima_actual'] ?? '');
        $momento_del_dia = $contextoActual['momento_del_dia'] ?? 'Día'; // Usar el valor existente, con 'Día' como fallback.

        Log::info("Contexto: Clima: {$clima_simple}, Momento: {$momento_del_dia}");

        foreach ($recommendations as $rec) {
            // Solo aplicar filtro a los nodos Destino (P)
            if ($rec['tipo_nodo'] !== 'P') {
                $resultados_filtrados[] = $rec;
                continue;
            }

            // Cargar metadata de Destino
            $destino = Destino::find($rec['id_referencia']);

            if (!$destino) continue;

            // Leer los campos de metadata
            $compatibilidad_clima = $destino->compatibilidad_clima ?? [];
            $horario_relevancia = $destino->horario_relevancia ?? 'Ambos';

            // === REGLA 1: Filtrado Climático ===
            // CORRECCIÓN: Si $clima_simple es vacío (clima no clasificable), pasa el filtro.
            $pasa_clima = empty($compatibilidad_clima) ||
                empty($clima_simple) || // ⭐ AÑADIDO: Si el clima no pudo ser simplificado, no se filtra.
                in_array($clima_simple, $compatibilidad_clima);

            // === REGLA 2: Filtrado Día/Noche ===
            // Usamos el valor $momento_del_dia recuperado del ContextoController.
            $pasa_horario = true;
            if (($horario_relevancia === 'Dia' && $momento_del_dia === 'Noche') ||
                ($horario_relevancia === 'Noche' && $momento_del_dia === 'Día') // Asegurar coincidencia de mayúsculas/minúsculas 'Día'/'Noche'
            ) {
                $pasa_horario = false; // El horario es incompatible
            }

            // --- Aplicar Filtros ---
            if ($pasa_clima && $pasa_horario) {
                $resultados_filtrados[] = $rec;
            } else {
                Log::debug("Destino #{$rec['id_referencia']} filtrado. Pasa Clima: " . ($pasa_clima ? 'Sí' : 'No') . ", Pasa Horario: " . ($pasa_horario ? 'Sí' : 'No'));
            }
        }

        return $resultados_filtrados;
    }


    // ==============================================================================
    // Funciones REST Generales (Se mantienen para completar la API CRUD)
    // ==============================================================================

    // ... (Mantener las funciones index, store, show, destroy, getByReference) ...

    /**
     * Display a listing of the resource.
     * GET /api/embeddings
     */
    public function index()
    {
        try {
            $embeddings = Embedding::all();

            $embeddings->each(function ($e) {
                $e->vector_embedding = json_decode($e->vector_embedding, true);
            });

            return response()->json($embeddings, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener los embeddings.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/embeddings
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'tipo_nodo' => 'required|in:U,P,C,E',
                'id_referencia' => 'required|integer',
                'vector_embedding' => 'required|string',
                'fecha_generacion' => 'required|date',
            ]);

            $embedding = Embedding::create($request->all());
            $embedding->vector_embedding = json_decode($embedding->vector_embedding, true);

            return response()->json($embedding, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para el embedding.', 'messages' => $e->errors()], 422);
        } catch (QueryException $e) {
            if ($e->getCode() == 23000) {
                return response()->json(['error' => 'Conflicto: Ya existe un embedding con esa referencia y versión.'], 409);
            }
            return response()->json(['error' => 'Error de base de datos.', 'message' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear el embedding.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/embeddings/{id_embedding}
     */
    public function show(string $id)
    {
        try {
            $embedding = Embedding::findOrFail($id);
            $embedding->vector_embedding = json_decode($embedding->vector_embedding, true);

            return response()->json($embedding, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Embedding no encontrado.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener el embedding.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/embeddings/{id_embedding}
     */
    public function destroy(string $id)
    {
        try {
            $embedding = Embedding::findOrFail($id);
            $embedding->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Embedding no encontrado para eliminar.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar el embedding.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Función adicional para obtener por tipo_nodo, id_referencia.
     * GET /api/embeddings/reference?tipo_nodo={}&id_referencia={}
     */
    public function getByReference(Request $request)
    {
        try {
            $request->validate([
                'tipo_nodo' => 'required|in:U,P,C,E',
                'id_referencia' => 'required|integer',
            ]);

            $embedding = Embedding::where('tipo_nodo', $request->tipo_nodo)
                ->where('id_referencia', $request->id_referencia)
                ->orderBy('created_at', 'desc')
                ->firstOrFail();

            $embedding->vector_embedding = json_decode($embedding->vector_embedding, true);

            return response()->json($embedding, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Embedding de referencia no encontrado.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al buscar el embedding por referencia.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * MÉTODO DE PRUEBA TEMPORAL: Prueba B2.1.3
     * Simula el contexto y los resultados de similitud para probar la lógica de filtrado.
     * GET /api/test/context-filter
     */
    public function testContextFilter()
    {
        // 1. Simular el Contexto (C) - Prueba con CLIMA SOLEADO
        // **Si ejecutas esto entre las 6 AM y 6:59 PM, será 'Día'. De lo contrario, 'Noche'.**
        $contextoActual = [
            'clima_actual' => 'Despejado y Soleado', // Se simplificará a 'Soleado'
            'temperatura' => 25,
        ];

        // 2. Simular Resultados de Similitud Bruta (B2.1.2)
        // Usamos IDs de referencia ficticios (101, 102, 103) que deben coincidir con tus Destinos de prueba en la DB.
        $similarityResults = [
            // ID 101: Alta Similitud. Asumimos metadata: Sol/Día.
            ['tipo_nodo' => 'P', 'id_referencia' => 101, 'similitud_coseno' => 0.90, 'vector' => [0.1, 0.2, 0.3]],
            // ID 102: Similitud Media. Asumimos metadata: Noche (Debería ser filtrado si es de día).
            ['tipo_nodo' => 'P', 'id_referencia' => 102, 'similitud_coseno' => 0.85, 'vector' => [0.4, 0.5, 0.6]],
            // ID 103: Similitud Baja. Asumimos metadata: Lluvioso (Debería ser filtrado si es Soleado).
            ['tipo_nodo' => 'P', 'id_referencia' => 103, 'similitud_coseno' => 0.80, 'vector' => [0.7, 0.8, 0.9]],
            // ID 200: Nodo Entidad (E). Siempre pasa el filtro de P.
            ['tipo_nodo' => 'E', 'id_referencia' => 200, 'similitud_coseno' => 0.70, 'vector' => [0.9, 0.8, 0.7]],
        ];

        // 3. Aplicar la Lógica de Filtrado (B2.1.3)
        $filteredResults = $this->filtrarPorContexto($similarityResults, $contextoActual);

        // 4. Preparar la respuesta para Postman
        $momento_del_dia = (now('America/Lima')->hour >= 6 && now('America/Lima')->hour < 19) ? 'Día' : 'Noche';
        $clima_simple = $this->simplificarClima($contextoActual['clima_actual']);

        return response()->json([
            'simulacion_contexto' => [
                'clima_actual_buscado' => $clima_simple,
                'momento_del_dia_actual' => $momento_del_dia,
                'hora_ejecucion_lima' => now('America/Lima')->toTimeString(),
            ],
            'resultados_similitud_bruta' => array_map(fn($r) => ['id' => $r['id_referencia'], 'similitud' => $r['similitud_coseno'], 'tipo' => $r['tipo_nodo']], $similarityResults),
            'resultados_filtrados' => array_map(fn($r) => ['id' => $r['id_referencia'], 'similitud' => $r['similitud_coseno'], 'tipo' => $r['tipo_nodo']], $filteredResults),
            'total_filtrados' => count($filteredResults),
        ]);
    }
    private function enriquecerSugerencias(array $sugerencias): array
    {
        // Obtener solo los IDs de los destinos sugeridos
        $destinoIds = collect($sugerencias)
            ->filter(fn($item) => $item['tipo_nodo'] === 'P') // Solo nos interesan los Destinos (P)
            ->pluck('id_referencia')
            ->unique()
            ->toArray();

        // Consultar la base de datos para obtener los detalles de todos los destinos en una sola consulta
        $destinosDetalles = Destino::whereIn('id_destino', $destinoIds)
            ->select('id_destino', 'nombre_destino', 'categoria', 'subcategoria', 'latitud', 'longitud',       'descripcion_corta', // Campo requerido para B2.3.2
        'foto_principal_url',)
            ->get()
            ->keyBy('id_destino'); // Usamos keyBy para un acceso O(1) rápido

        $sugerenciasEnriquecidas = [];

        foreach ($sugerencias as $sugerencia) {
            if ($sugerencia['tipo_nodo'] === 'P') {
                $id = $sugerencia['id_referencia'];

                // Si encontramos el detalle del destino en la colección
                if ($destinosDetalles->has($id)) {
                    $detalle = $destinosDetalles->get($id);

                    $sugerenciasEnriquecidas[] = [
                        'id_destino' => (int) $id,
                        'nombre_destino' => $detalle->nombre_destino,
                        'categoria' => $detalle->categoria,
                        'subcategoria' => $detalle->subcategoria,
                        'similitud_coseno' => $sugerencia['similitud_coseno'],
                        // Puedes añadir más detalles necesarios para el mapa o la interfaz de usuario
                        'latitud' => $detalle->latitud,
                        'longitud' => $detalle->longitud,
                        'descripcion_corta' => $detalle->descripcion_corta,
                        'foto_principal_url' => $detalle->foto_principal_url,
                        // Nota: Aquí podrías añadir un campo para la URL de una imagen
                    ];
                }
            }
        }

        // Opcional: Reordenar por similitud si el ranking inicial no estaba perfectamente ordenado (aunque debería)
        return collect($sugerenciasEnriquecidas)->sortByDesc('similitud_coseno')->values()->toArray();
    }
}
