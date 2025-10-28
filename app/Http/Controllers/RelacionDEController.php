<?php

namespace App\Http\Controllers;

use App\Models\RelacionDE;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

class RelacionDEController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/relaciones/de
     */
    public function index()
    {
        try {
            $relaciones = RelacionDE::with(['destino', 'evento'])->get();
            return response()->json($relaciones, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener las relaciones D-E.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/relaciones/de
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_destino' => 'required|exists:destinos,id_destino',
                'id_evento' => 'required|exists:evento_festividades,id_evento',
                'tipo_vinculo' => 'required|string|max:50',
                'impacto_turistico' => 'nullable|numeric|min:0|max:1',
            ]);

            $relacion = RelacionDE::create($request->all());

            return response()->json($relacion, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para la relación D-E.', 'messages' => $e->errors()], 422);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos. IDs no válidos o relación duplicada.'], 409);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear la relación D-E.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove a specific resource.
     * DELETE /api/relaciones/de/{id_destino}/{id_evento}
     */
    public function destroyByKeys(string $destinoId, string $eventoId)
    {
        try {
            $deleted = RelacionDE::where([
                'id_destino' => $destinoId,
                'id_evento' => $eventoId
            ])->delete();

            if ($deleted === 0) {
                return response()->json(['error' => 'Relación D-E no encontrada para eliminar.'], 404);
            }

            return response()->json(null, 204);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar la relación D-E.', 'message' => $e->getMessage()], 500);
        }
    }
}
