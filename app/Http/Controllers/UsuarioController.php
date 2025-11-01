<?php

namespace App\Http\Controllers;

use App\Models\Embedding;
use App\Models\InteraccionUC;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use App\Services\EmbeddingProcessor;


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
        try {
            // 1. Validación (Paso 1)
            $request->validate([
                'nombre' => 'required|string|max:100',
                'preferencias_texto' => 'required|string',
                'tipo_turista' => 'required|string',
                // ...
            ]);

            // 2. INSERT Usuario (Paso 2)
            $usuario = Usuario::create(array_merge($request->all(), [
                'dispositivo_acceso' => $request->header('User-Agent', 'Desconocido'),
            ]));
            $nuevo_id_usuario = $usuario->id_usuario;

            // 3. Consulta de Contexto (Paso 3)
            $contextoController = new ContextoController();
            $contextoResponse = $contextoController->obtenerContextoActual($request);

            if ($contextoResponse->getStatusCode() !== 200) {
                return $contextoResponse; // Falla si no se encuentra Contexto
            }

            $contexto_actual = json_decode($contextoResponse->getContent(), true);
            $id_contexto_recuperado = $contexto_actual['id_contexto'];

            // Buscar el Vector C^0 asociado al contexto (Tabla Embeddings)
            $vector_c0_obj = Embedding::where('id_referencia', $id_contexto_recuperado)
                ->where('tipo_nodo', 'C') // 'C' es para Contexto
                ->firstOrFail();
            $vector_c0_real = json_decode($vector_c0_obj->vector_embedding, true);

            // 4. Inserción en InteraccionUC (Paso 4) - Inicializa el peso a 0.0
            InteraccionUC::create([
                'id_usuario' => $nuevo_id_usuario,
                'id_contexto' => $id_contexto_recuperado,
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
                $vector_c0_real
            );

            // 6. Respuesta Exitosa (Paso 6)
            return response()->json([
                'message' => 'Perfil creado con éxito. ¡Explora Tarma Inteligente!',
                'usuario' => $usuario,
                'embedding_status' => $success_embedding ? 'OK' : 'Error/Pendiente'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos.', 'messages' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            // Captura si falla al buscar el Vector C^0 
            return response()->json(['error' => 'Error de IA: Vector de Contexto (C0) no encontrado. Asegúrese de ejecutar VectorizeContextos.', 'message' => $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear el usuario o el perfil inteligente.', 'message' => $e->getMessage()], 500);
        }
    }

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
            // Usuario no encontrado
            return response()->json(['error' => 'Usuario no encontrado.'], 404); // 404 Not Found
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
                // ...
            ]);

            $usuario->update($request->all());

            return response()->json($usuario, 200); // 200 OK
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
            return response()->json(null, 204); // 204 No Content (Éxito sin contenido de respuesta)
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Usuario no encontrado para eliminar.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar el usuario.', 'message' => $e->getMessage()], 500);
        }
    }
}
