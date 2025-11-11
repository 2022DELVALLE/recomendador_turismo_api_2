<?php

namespace App\Http\Controllers;

use App\Models\InteraccionUD; // Modelo para insertar en interaccion_usuario_destino
use App\Models\Usuario;      // Modelo para validar la existencia del usuario
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use App\Services\EmbeddingProcessor; // Clase para la lógica de ajuste vectorial U0 -> U1
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecomendacionFeedbackController extends Controller
{
    protected $embeddingProcessor;

    // Inyectamos el EmbeddingProcessor para el ajuste U0 -> U1
    public function __construct(EmbeddingProcessor $embeddingProcessor)
    {
        $this->embeddingProcessor = $embeddingProcessor;
    }

    // =========================================================================
    // LÓGICA DE CONSULTA DE RESEÑAS (TAREA B3.3.3)
    // =========================================================================

    /**
     * Helper para traducir la puntuación numérica del sentimiento a una etiqueta.
     * Basado en un rango de -1.0 a 1.0 (propio de modelos NLP).
     * @param float|null $score
     * @return string
     */
    private function getSentimientoLabel($score): string
    {
        if (is_null($score)) {
            return 'No Analizado';
        }
        // Asumiendo umbrales de +/- 0.25 para determinar neutro
        if ($score >= 0.25) {
            return 'Positivo';
        }
        if ($score <= -0.25) {
            return 'Negativo';
        }
        return 'Neutro';
    }

    /**
     * GET /api/destinos/{id_destino}/reseñas
     * Tarea B3.3.3: Consulta todas las reseñas y sus datos asociados para un destino.
     *
     * @param int $id_destino
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerReseñasPorDestino(int $id_destino)
    {
        try {
            // Buscamos las interacciones que están relacionadas con una reseña textual explícita
            // Filtramos por 'REVIEW' para obtener solo reseñas completas (con rating y texto).
            $reviews = InteraccionUD::query()
                ->where('id_destino', $id_destino)
                ->where('tipo_interaccion', 'REVIEW')
                ->orderBy('created_at', 'desc')
                ->limit(20) // Limitamos a las 20 reseñas más recientes
                ->with(['usuario:id_usuario,nombre', 'reseña']) // Carga eager loading
                ->get();

            // Mapeamos y simplificamos la estructura de la respuesta
            $reseñasFormateadas = $reviews->map(function ($interaccion) {

                // Extraemos el contenido original de la reseña desde la tabla reseña_texto
                $comentarioTexto = $interaccion->reseña
                    ? $interaccion->reseña->contenido_original
                    : 'Reseña sin contenido de texto';

                // Extraemos y traducimos la puntuación numérica del sentimiento
                $puntuacion = $interaccion->reseña ? $interaccion->reseña->puntuacion_sentimiento : null;
                $sentimientoLabel = $this->getSentimientoLabel($puntuacion);

                return [
                    'id_interaccion' => $interaccion->id_interaccion,
                    'rating' => $interaccion->rating,
                    'fecha' => $interaccion->created_at->format('Y-m-d H:i'),
                    'usuario' => $interaccion->usuario->nombre ?? 'Usuario Anónimo',
                    'comentario' => $comentarioTexto,
                    'sentimiento_score' => $puntuacion,
                    'sentimiento_label' => $sentimientoLabel,
                ];
            })
                // Quitamos las reseñas que hayan quedado sin texto
                ->filter(fn($r) => !empty($r['comentario']));


            if ($reseñasFormateadas->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron reseñas para este destino.',
                    'destino_id' => $id_destino,
                    'reseñas' => [],
                ], 200);
            }

            return response()->json([
                'destino_id' => $id_destino,
                'total_reseñas' => $reseñasFormateadas->count(),
                'reseñas' => $reseñasFormateadas->values(),
            ], 200);
        } catch (Exception $e) {
            Log::error("Error al obtener reseñas para el destino $id_destino: " . $e->getMessage());
            return response()->json([
                'error' => 'Error inesperado del servidor al consultar reseñas.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // REGISTRO DE INTERACCIONES (YA IMPLEMENTADO)
    // =========================================================================

    /**
     * POST /api/usuario/{user_id}/interaccion_visualizacion
     * Registra las visualizaciones (B2.5.2) y ajusta el embedding del usuario (B2.5.3).
     *
     * @param Request $request
     * @param int $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function registrarVisualizacion(Request $request, int $user_id)
    {
        // 1. Requisito B2.5.1: Validación de la solicitud
        try {
            $request->validate([
                // Espera un array de IDs de destino
                'destino_ids' => 'required|array|min:1',
                // Asegura que los IDs existan en la tabla 'destinos'
                'destino_ids.*' => 'integer|exists:destinos,id_destino',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para la interacción.', 'messages' => $e->errors()], 422);
        }

        $destino_ids = $request->input('destino_ids');

        try {
            // Verificar la existencia del usuario antes de la transacción
            if (!Usuario::where('id_usuario', $user_id)->exists()) {
                throw new ModelNotFoundException("Usuario no encontrado con ID: {$user_id}");
            }

            // Iniciar la transacción de base de datos
            DB::beginTransaction();

            // =========================================================================
            // 2. Tarea B2.5.2: Inserción en la Base de Datos (Registro de Eventos)
            // =========================================================================

            $registros_creados = 0;
            $fecha_hora_actual = now();

            // Registramos cada destino visto
            foreach ($destino_ids as $id_destino) {
                InteraccionUD::create([
                    'id_usuario' => $user_id,
                    'id_destino' => $id_destino,
                    'rating' => 1.0, // Rating implícito: interacciones de visualización
                    'fecha_visita' => $fecha_hora_actual->toDateString(),
                    'duracion_visita' => 0,
                    'gasto_estimado' => 0,
                    'comentario' => 'Interaccion: visualizacion (Implícito GNN)',
                    'medio_transporte' => 'Web',
                ]);
                $registros_creados++;
            }

            // =========================================================================
            // 3. Tarea B2.5.3: Lógica de Ajuste del Embedding (U0 -> U1)
            // =========================================================================

            $success = $this->embeddingProcessor->ajustarEmbeddingPorVisualizacion($user_id, $destino_ids);

            if (!$success) {
                DB::rollBack();
                // Si falla el ajuste del vector (ej. no encuentra el vector U0), revertimos los registros
                return response()->json(['error' => 'Error al actualizar el perfil inteligente (Vectorial fallido).'], 500);
            }

            DB::commit();

            // 4. Respuesta (B2.5.1)
            return response()->json([
                'message' => 'Interacciones de visualización registradas y perfil de usuario inteligente actualizado.',
                'registros_creados' => $registros_creados,
                'user_id' => $user_id
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Usuario o uno de los destinos no fue encontrado.'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error general en registrarVisualizacion: " . $e->getMessage());
            return response()->json(['error' => 'Error inesperado del servidor.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/usuario/{user_id}/interaccion_explicita
     * Registra una interacción explícita (como 'Me Gusta', 'Reseña') para un destino único
     * y ajusta el embedding del usuario usando ajustarEmbeddingPorInteraccionUnica.
     *
     * @param Request $request
     * @param int $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function registrarInteraccionExplicita(Request $request, $user_id)
    {
        // 1. Validación de la solicitud para interacción única y explícita
        try {
            $request->validate([
                'id_destino' => 'required|integer|exists:destinos,id_destino',
                // Rating de 1.0 (No me gusta/odio) a 5.0 (Me encanta)
                'rating' => 'required|numeric|min:1.0|max:5.0',
                'tipo_interaccion' => 'required|string|in:LIKE,REVIEW,BOOKMARK', // Tipos de interacción explícita
                'comentario' => 'nullable|string|max:500',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para la interacción explícita.', 'messages' => $e->errors()], 422);
        }

        $id_destino = $request->input('id_destino');
        $rating = $request->input('rating');
        $tipo_interaccion = $request->input('tipo_interaccion');
        // Usamos comillas dobles para interpolar la variable $tipo_interaccion
        $comentario = $request->input('comentario') ?? "Interaccion: {$tipo_interaccion}";

        try {
            // Verificar la existencia del usuario antes de la transacción
            if (!Usuario::where('id_usuario', $user_id)->exists()) {
                throw new ModelNotFoundException("Usuario no encontrado con ID: {$user_id}");
            }

            // Iniciar la transacción de base de datos
            DB::beginTransaction();

            // =========================================================================
            // 2. Inserción en la Base de Datos (Registro de Evento Explícito)
            // =========================================================================

            InteraccionUD::create([
                'id_usuario' => $user_id,
                'id_destino' => $id_destino,
                'rating' => $rating,
                'fecha_visita' => now()->toDateString(),
                'duracion_visita' => 0, // No aplica directamente
                'gasto_estimado' => 0, // No aplica directamente
                'comentario' => $comentario,
                'medio_transporte' => $tipo_interaccion, // Usamos el tipo para registrar el medio
            ]);

            // =========================================================================
            // 3. Lógica de Ajuste del Embedding con Interacción Única (U0 -> U1)
            // =========================================================================

            $success = $this->embeddingProcessor->ajustarEmbeddingPorInteraccionUnica(
                $user_id,
                $id_destino,
                $tipo_interaccion
            );

            if (!$success) {
                DB::rollBack();
                // Si falla el ajuste del vector (ej. no encuentra el vector U0), revertimos los registros
                return response()->json(['error' => 'Error al actualizar el perfil inteligente (Vectorial fallido).'], 500);
            }

            DB::commit();

            // 4. Respuesta
            return response()->json([
                'message' => 'Interacción explícita registrada y perfil de usuario inteligente actualizado.',
                'user_id' => $user_id,
                'id_destino' => $id_destino,
                'rating' => $rating
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Usuario o destino no encontrado.'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error general en registrarInteraccionExplicita: " . $e->getMessage());
            return response()->json(['error' => 'Error inesperado del servidor.', 'message' => $e->getMessage()], 500);
        }
    }
}
