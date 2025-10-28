<?php

namespace App\Http\Controllers;

use App\Models\Embedding;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

class EmbeddingController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/embeddings
     */
    public function index()
    {
        try {
            $embeddings = Embedding::all();
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
                'tipo_nodo' => 'required|in:U,P,C,E', // Aseguramos que sea un nodo válido
                'id_referencia' => 'required|integer',
                'vector_embedding' => 'required|string', // Se espera un JSON string o texto largo
                'fecha_generacion' => 'required|date',
            ]);

            $embedding = Embedding::create($request->all());

            return response()->json($embedding, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para el embedding.', 'messages' => $e->errors()], 422);
        } catch (QueryException $e) {
            if ($e->getCode() == 23000) { // Código SQL para violación de clave única
                return response()->json(['error' => 'Ya existe un embedding para ese tipo de nodo y referencia.'], 409); // 409 Conflict
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

    // Función adicional para obtener por tipo_nodo y id_referencia
    public function getByReference(Request $request)
    {
        try {
            $request->validate([
                'tipo_nodo' => 'required|in:U,P,C,E',
                'id_referencia' => 'required|integer',
            ]);

            $embedding = Embedding::where('tipo_nodo', $request->tipo_nodo)
                ->where('id_referencia', $request->id_referencia)
                ->firstOrFail();

            return response()->json($embedding, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Embedding de referencia no encontrado.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al buscar el embedding por referencia.', 'message' => $e->getMessage()], 500);
        }
    }
}
