<?php

namespace App\Http\Controllers;

use App\Models\Embedding;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

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


    // ==============================================================================
    // B2.2.1: Propagación y Agregación de Embeddings (Generación de U₁)
    // ==============================================================================

    /**
     * Simula la propagación GNN: Agrega los embeddings de los 3 nodos más similares
     * para generar un nuevo embedding de usuario (U₁).
     * POST /api/usuario/{id_usuario}/propagate
     */
    public function propagateAndAggregate(string $id_usuario, bool $save_new_embedding = true)
    {
        try {
            // 1. Obtener el Embedding Inicial del Usuario ($U_0$)
            $userEmbeddingRecord = Embedding::where('tipo_nodo', 'U')
                ->where('id_referencia', $id_usuario)
                ->orderBy('created_at', 'desc')
                ->firstOrFail(); // Usamos el más reciente (podría ser U₀, U₁, U₂, etc.)

            $userVector = json_decode($userEmbeddingRecord->vector_embedding, true);
            $vectorDimension = count($userVector);

            if (empty($userVector)) {
                return response()->json(['error' => 'El vector de embedding del usuario está vacío o es inválido.'], 500);
            }

            // 2. Obtener los Embeddings de los Nodos Candidatos (P, C, E)
            $candidateEmbeddings = Embedding::whereIn('tipo_nodo', ['P', 'C', 'E'])->get();

            $similarityResults = [];

            // 3. Calcular la Similitud de Coseno para cada candidato
            $candidateVectors = [];
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

            // 4. Ordenar y obtener los 3 mejores (Top-3)
            usort($similarityResults, function ($a, $b) {
                return $b['similitud_coseno'] <=> $a['similitud_coseno'];
            });

            $topRecommendations = array_slice($similarityResults, 0, 3);

            // 5. Función de Agregación (Generación de U₁)
            // Implementaremos la Agregación Promedio (Mean Aggregation)
            $aggregatedVector = array_fill(0, $vectorDimension, 0.0);
            $totalVectors = 0;

            // Incluir el vector inicial ($U_n$)
            $currentVector = $userVector;
            for ($i = 0; $i < $vectorDimension; $i++) {
                $aggregatedVector[$i] += $currentVector[$i];
            }
            $totalVectors++;

            // Incluir los 3 vectores recomendados ($E_{top3}$)
            $topVectors = [];
            foreach ($topRecommendations as $rec) {
                $topVectors[] = $rec['vector'];
                for ($i = 0; $i < $vectorDimension; $i++) {
                    $aggregatedVector[$i] += $rec['vector'][$i];
                }
                $totalVectors++;
            }

            // Calcular el promedio
            $meanVector = array_map(fn($val) => $val / $totalVectors, $aggregatedVector);

            // 6. Refinamiento (Normalización para obtener U₁)
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

            // Limpiar los vectores de la respuesta de recomendaciones (para ser más ligeros)
            $recommendationsOnly = array_map(function ($rec) {
                unset($rec['vector']);
                return $rec;
            }, $topRecommendations);

            // 8. Devolver la respuesta formateada
            return response()->json([
                'id_usuario' => (int) $id_usuario,
                'embedding_id_inicial' => (int) $userEmbeddingRecord->id_embedding,
                'recomendaciones' => $recommendationsOnly,
                'nuevo_embedding_id' => $newEmbeddingId,
                'nuevo_embedding_U1' => $newEmbeddingId ? $newVectorU1 : 'No guardado (solo similitud)',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Embedding de usuario (' . $id_usuario . ') no encontrado. No se puede propagar la recomendación.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error en la propagación y agregación GNN.', 'message' => $e->getMessage()], 500);
        }
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
}
