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
}
