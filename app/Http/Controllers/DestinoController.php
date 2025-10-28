<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class DestinoController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/destinos
     */
    public function index()
    {
        try {
            $destinos = Destino::all();
            return response()->json($destinos, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener los destinos.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/destinos
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre_destino' => 'required|string|max:100',
                'categoria' => 'required|string|max:50',
                'latitud' => 'required|numeric',
                'longitud' => 'required|numeric',
            ]);

            $destino = Destino::create($request->all());
            
            return response()->json($destino, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para el destino.', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear el destino.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/destinos/{id_destino}
     */
    public function show(string $id)
    {
        try {
            $destino = Destino::findOrFail($id);
            return response()->json($destino, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Destino no encontrado.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener el destino.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/destinos/{id_destino}
     */
    public function update(Request $request, string $id)
    {
        try {
            $destino = Destino::findOrFail($id);
            
            $request->validate([
                'nombre_destino' => 'sometimes|required|string|max:100',
                'categoria' => 'sometimes|required|string|max:50',
            ]);

            $destino->update($request->all());
            
            return response()->json($destino, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Destino no encontrado para actualizar.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de actualización inválidos para el destino.', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo actualizar el destino.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/destinos/{id_destino}
     */
    public function destroy(string $id)
    {
        try {
            $destino = Destino::findOrFail($id);
            $destino->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Destino no encontrado para eliminar.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar el destino.', 'message' => $e->getMessage()], 500);
        }
    }
}