<?php

namespace App\Http\Controllers;

use App\Models\PrediccionRating;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

class PrediccionRatingController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/predicciones
     */
    public function index()
    {
        try {
            $predicciones = PrediccionRating::with(['usuario', 'destino'])->get();
            return response()->json($predicciones, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener las predicciones.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/predicciones
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_usuario' => 'required|exists:usuarios,id_usuario',
                'id_destino' => 'required|exists:destinos,id_destino',
                'rating_predicho' => 'required|numeric|min:1|max:5',
                'modelo_usado' => 'required|string|max:50',
                'fecha_prediccion' => 'required|date',
                // Validar clave única U-P: id_usuario y id_destino deben ser únicos
                'id_usuario' => 'unique:prediccion_rating,id_usuario,NULL,id_prediccion,id_destino,' . $request->id_destino,
            ]);

            $prediccion = PrediccionRating::create($request->all());

            return response()->json($prediccion, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para la predicción.', 'messages' => $e->errors()], 422);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Ya existe una predicción para este par Usuario-Destino. Utilice PUT para actualizar.'], 409);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear la predicción.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/predicciones/{id_prediccion}
     */
    public function show(string $id)
    {
        try {
            $prediccion = PrediccionRating::with(['usuario', 'destino'])->findOrFail($id);
            return response()->json($prediccion, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Predicción no encontrada.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener la predicción.', 'message' => $e->getMessage()], 500);
        }
    }

    // Se recomienda usar PUT/PATCH para actualizar la predicción si se vuelve a ejecutar el modelo.
    public function update(Request $request, string $id)
    {
        try {
            $prediccion = PrediccionRating::findOrFail($id);

            $request->validate([
                'rating_predicho' => 'sometimes|required|numeric|min:1|max:5',
                'modelo_usado' => 'sometimes|required|string|max:50',
            ]);

            $prediccion->update($request->all());

            return response()->json($prediccion, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Predicción no encontrada para actualizar.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de actualización inválidos.', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo actualizar la predicción.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/predicciones/{id_prediccion}
     */
    public function destroy(string $id)
    {
        try {
            PrediccionRating::findOrFail($id)->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Predicción no encontrada para eliminar.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar la predicción.', 'message' => $e->getMessage()], 500);
        }
    }
}
