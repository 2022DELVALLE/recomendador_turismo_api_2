<?php

namespace App\Http\Controllers;

use App\Models\Embedding;
use App\Models\InteraccionUC;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use App\Services\EmbeddingProcessor;
use Illuminate\Support\Facades\Log;
use App\Models\Contexto;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/usuarios
     */
    public function index()
    {
        try {
            $usuarios = Usuario::all();
            return response()->json($usuarios, 200); // 200 OK
        } catch (Exception $e) {
            // Error de servidor o base de datos
            return response()->json(['error' => 'No se pudieron obtener los usuarios.', 'message' => $e->getMessage()], 500); // 500 Internal Server Error
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/usuarios
     */
    public function store(Request $request)
    {
        // Variable para almacenar los datos RAW del contexto antes de cualquier excepción
        $raw_contexto_data = [];

        try {
            // 1. Validación (Paso 1)
            $request->validate([
                'nombre' => 'required|string|max:100',
                'preferencias_texto' => 'required|string',
                'tipo_turista' => 'required|string',
                'edad' => 'nullable|integer',
                'genero' => 'nullable|string',
                'origen_geografico' => 'nullable|string',
                'nivel_gasto' => 'nullable|numeric',
            ]);

            // 2. INSERT Usuario (Paso 2)
            $data = $request->all();
            // Nota: Si el frontend NO envia 'dispositivo_acceso', puedes establecer un fallback simple:
            if (!isset($data['dispositivo_acceso']) || empty($data['dispositivo_acceso'])) {
                $data['dispositivo_acceso'] = 'No Seleccionado';
            }
            $usuario = Usuario::create($data);

            $nuevo_id_usuario = $usuario->id_usuario;

            // --------------------------------------------------------
            // 3. Consulta y Mapeo del Contexto Vectorizado (Paso 3)
            // --------------------------------------------------------

            // 3a. Obtener el contexto en vivo (RAW data) del ContextoController
            $contextoController = new ContextoController();
            $contextoResponse = $contextoController->obtenerContextoActual($request);

            // Manejo del Fallback (status 503) o éxito (status 200)
            if ($contextoResponse->getStatusCode() !== 200) {
                // Si falla (ej. 503 Fallback), usamos los datos por defecto del Fallback.
                $raw_contexto_data = json_decode($contextoResponse->getContent(), true);
                Log::warning("El servicio de contexto en vivo falló o usó Fallback. Usando datos: " . json_encode($raw_contexto_data));
            } else {
                $raw_contexto_data = json_decode($contextoResponse->getContent(), true);
            }

            // 3b. Mapear el contexto RAW al Contexto Vectorizado (ID y Vector C0)
            $contexto_vectorizado = $this->resolverContextoVectorizado($raw_contexto_data);

            $id_contexto_recuperado = $contexto_vectorizado['id_contexto'];
            $vector_c0_real = $contexto_vectorizado['vector_c0'];

            // --------------------------------------------------------

            // 4. Inserción en InteraccionUC (Paso 4) - Inicializa el peso a 0.0
            InteraccionUC::create([
                'id_usuario' => $nuevo_id_usuario,
                'id_contexto' => $id_contexto_recuperado, // ID obtenido del mapeo para la relación U-C
                'peso_uc' => 0.0,
                'servicios_utilizados' => false,
            ]);

            // 5. Generación de Embedding y Peso W_UC (Paso 5)
            $processor = new EmbeddingProcessor();
            $texto_usuario_combinado = $request->input('preferencias_texto') . ' ' . $request->input('tipo_turista');

            $success_embedding = $processor->procesarRegistro(
                $nuevo_id_usuario,
                $id_contexto_recuperado,
                $texto_usuario_combinado,
                $vector_c0_real // Vector C0 obtenido del mapeo para el cálculo de U^0
            );

            // 6. Respuesta Exitosa (Paso 6)
            return response()->json([
                'message' => 'Perfil creado con éxito. ¡Explora Tarma Inteligente!',
                'usuario' => $usuario,
                'id_contexto_asociado' => $id_contexto_recuperado,
                'embedding_status' => $success_embedding ? 'OK' : 'Error/Pendiente'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos.', 'messages' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            // Se puede capturar el clima simplificado aquí para el log y el mensaje de error.
            $clima_actual_raw = $raw_contexto_data['clima_actual'] ?? 'N/A';
            $clima_simplificado = $this->simplificarClima($clima_actual_raw);
            Log::error("Error de ModelNotFound: Contexto vectorizado (ID/C0) no encontrado en DB. Mensaje: " . $e->getMessage() . " Se intentó buscar el clima: " . $clima_simplificado);
            return response()->json(['error' => 'Error de IA: Contexto Vectorizado no encontrado en la base de datos. Asegúrese de que el contexto mapeado exista (Ej: ' . $clima_simplificado . ').', 'message' => $e->getMessage()], 500);
        } catch (Exception $e) {
            Log::error("Error general en store: " . $e->getMessage());
            return response()->json(['error' => 'No se pudo crear el usuario o el perfil inteligente.', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Resuelve los datos del Contexto Actual (RAW data) al ID de Contexto vectorizado (C) y su vector C^0.
     * Busca el contexto en la tabla 'contextos' y su vector asociado en 'embeddings'.
     * @param array $rawContextoData Datos del contexto actual (clima_actual, momento_del_dia).
     * @return array [id_contexto, vector_c0]
     */
    protected function resolverContextoVectorizado(array $rawContextoData): array
    {
        // 1. Obtener y simplificar el atributo clave del clima
        $clima_actual = $rawContextoData['clima_actual'] ?? 'Templado y Nublado (Fallback)';

        $clima_simplificado = $this->simplificarClima($clima_actual);

        // 2. Buscar el registro de Contexto DB que coincida con el clima simplificado (SELECT Contexto)
        // CORRECCIÓN DE ROBUSTEZ: Usamos LOWER() en la base de datos y en el valor buscado para insensibilidad a mayúsculas.
        $contexto_obj = Contexto::whereRaw('LOWER(clima_actual) = ?', [strtolower($clima_simplificado)])
            ->firstOrFail(); // Lanza ModelNotFoundException si no existe

        $id_contexto = $contexto_obj->id_contexto;

        // 3. Buscar el Vector C^0 asociado en la tabla Embeddings (SELECT Embeddings)
        $vector_c0_obj = Embedding::where('id_referencia', $id_contexto)
            ->where('tipo_nodo', 'C') // 'C' es para Contexto
            ->firstOrFail();

        // El vector viene como JSON string y debe decodificarse a un array PHP
        $vector_c0_real = json_decode($vector_c0_obj->vector_embedding, true);

        return [
            'id_contexto' => $id_contexto,
            'vector_c0' => $vector_c0_real,
        ];
    }
    /**
     * Función auxiliar para simplificar el clima, mapeando los descriptores RAW
     * a los nombres exactos de contexto vectorizado en la base de datos (DB).
     * @param string $clima_actual Clima reportado por el servicio externo (ej. "Muy nuboso").
     * @return string Clima simplificado (Debe coincidir con los valores de la DB, ej. 'Templado y Soleado').
     */
    protected function simplificarClima(string $clima_actual): string
    {
        $clima_actual = strtolower($clima_actual);

        // Mapeo a 'Lluvioso' (debe ser el nombre exacto en tu DB)
        if (str_contains($clima_actual, 'lluvia') || str_contains($clima_actual, 'tormenta') || str_contains($clima_actual, 'llovizna') || str_contains($clima_actual, 'aguacero')) {
            return 'Lluvioso';
        }

        // Mapea a 'Templado y Soleado'
        if (str_contains($clima_actual, 'sol') || str_contains($clima_actual, 'despejado')) {
            return 'Templado y Soleado';
        }

        // Mapea a 'Templado y Nublado' (incluye Fallback)
        if (str_contains($clima_actual, 'nublado') || str_contains($clima_actual, 'templado') || str_contains($clima_actual, 'muy nuboso') || str_contains($clima_actual, 'cubierto') || str_contains($clima_actual, 'fallback')) {
            return 'Templado y Nublado';
        }

        // Estado por defecto
        return 'Templado y Nublado';
    }


    // MÉTODOS show, update, destroy se mantienen sin cambios

    /**
     * Display the specified resource.
     * GET /api/usuarios/{id_usuario}
     */
    public function show(string $id)
    {
        try {
            $usuario = Usuario::findOrFail($id);
            return response()->json($usuario, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Usuario no encontrado.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener el usuario.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/usuarios/{id_usuario}
     */
    public function update(Request $request, string $id)
    {
        try {
            $usuario = Usuario::findOrFail($id);

            $request->validate([
                'nombre' => 'sometimes|required|string|max:100',
                'preferencias_texto' => 'sometimes|required|string',
                'tipo_turista' => 'sometimes|required|string',
                'edad' => 'nullable|integer',
                'genero' => 'nullable|string',
                'origen_geografico' => 'nullable|string',
                'nivel_gasto' => 'nullable|numeric',
            ]);

            $usuario->update($request->all());

            return response()->json($usuario, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Usuario no encontrado para actualizar.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de actualización inválidos.', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo actualizar el usuario.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/usuarios/{id_usuario}
     */
    public function destroy(string $id)
    {
        try {
            $usuario = Usuario::findOrFail($id);
            $usuario->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Usuario no encontrado para eliminar.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar el usuario.', 'message' => $e->getMessage()], 500);
        }
    }
}
