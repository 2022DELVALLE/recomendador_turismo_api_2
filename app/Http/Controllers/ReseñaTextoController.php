// app/Http/Controllers/ReseñaTextoController.php

<?php

namespace App\Http\Controllers;

use App\Models\ReseñaTexto;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

class ReseñaTextoController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/reviews
     */
    public function index()
    {
        try {
            $reviews = ReseñaTexto::with('interaccionUD')->get();
            return response()->json($reviews, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener las reseñas.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/reviews
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                // Clave única 1:1 con la interacción
                'id_interaccion' => 'required|exists:interaccion_usuario_destino,id_interaccion|unique:reseña_texto,id_interaccion',
                'contenido_original' => 'required|string',
            ]);

            $reseña = ReseñaTexto::create($request->all());

            return response()->json($reseña, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para la reseña.', 'messages' => $e->errors()], 422);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos. La interacción ya tiene una reseña asociada.'], 409);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear la reseña.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/reviews/{id_reseña}
     */
    public function show(string $id)
    {
        try {
            $reseña = ReseñaTexto::with('interaccionUD')->findOrFail($id);
            return response()->json($reseña, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Reseña no encontrada.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener la reseña.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/reviews/{id_reseña}
     */
    public function destroy(string $id)
    {
        try {
            ReseñaTexto::findOrFail($id)->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Reseña no encontrada para eliminar.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar la reseña.', 'message' => $e->getMessage()], 500);
        }
    }
}
