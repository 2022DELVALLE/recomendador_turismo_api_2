<?php

namespace App\Http\Controllers;

use App\Models\RelacionCD;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Exception;

class RelacionCDController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/relaciones/cd
     */
    public function index()
    {
        try {
            $relaciones = RelacionCD::with(['contexto', 'destino'])->get();
            return response()->json($relaciones, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener las relaciones C-D.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/relaciones/cd
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_contexto' => 'required|exists:contextos,id_contexto',
                'id_destino' => 'required|exists:destinos,id_destino',
                'impacto_clima' => 'nullable|string|max:50',
                'peso_contexto' => 'required|numeric|min:0',
                'es_accesible' => 'required|boolean',
            ]);

            $relacion = RelacionCD::create($request->all());

            return response()->json($relacion, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para la relación C-D.', 'messages' => $e->errors()], 422);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos. IDs no válidos o relación duplicada.'], 409);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear la relación C-D.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove a specific resource.
     * DELETE /api/relaciones/cd/{id_contexto}/{id_destino}
     */
    public function destroyByKeys(string $contextoId, string $destinoId)
    {
        try {
            $deleted = RelacionCD::where([
                'id_contexto' => $contextoId,
                'id_destino' => $destinoId
            ])->delete();

            if ($deleted === 0) {
                return response()->json(['error' => 'Relación C-D no encontrada para eliminar.'], 404);
            }

            return response()->json(null, 204);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar la relación C-D.', 'message' => $e->getMessage()], 500);
        }
    }
}
