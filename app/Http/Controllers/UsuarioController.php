<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

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
            $request->validate([
                'nombre' => 'required|string|max:100',
                'edad' => 'nullable|integer',
                // ... otras validaciones
            ]);

            $usuario = Usuario::create($request->all());

            return response()->json($usuario, 201); // 201 Created
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Error de validación de datos de entrada
            return response()->json(['error' => 'Datos de entrada inválidos.', 'messages' => $e->errors()], 422); // 422 Unprocessable Entity
        } catch (Exception $e) {
            // Error de servidor
            return response()->json(['error' => 'No se pudo crear el usuario.', 'message' => $e->getMessage()], 500);
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
