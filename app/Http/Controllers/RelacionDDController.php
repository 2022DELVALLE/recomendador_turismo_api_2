<?php

namespace App\Http\Controllers;

use App\Models\RelacionDD;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Exception;

class RelacionDDController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/relaciones/dd
     */
    public function index()
    {
        try {
            $relaciones = RelacionDD::with(['destinoOrigen', 'destinoRelacionado'])->get();
            return response()->json($relaciones, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener las relaciones D-D.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/relaciones/dd
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_destino_origen' => 'required|exists:destinos,id_destino',
                'id_destino_relacionado' => 'required|exists:destinos,id_destino|different:id_destino_origen', // No relacionar consigo mismo
                'tipo_relacion' => 'required|string|max:50',
                'peso_relacion' => 'required|numeric|min:0',
            ]);

            $relacion = RelacionDD::create($request->all());

            return response()->json($relacion, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para la relación D-D.', 'messages' => $e->errors()], 422);
        } catch (QueryException $e) {
            // 23000: Foreign Key violation o Unique constraint (clave primaria compuesta)
            return response()->json(['error' => 'Error de base de datos. IDs no válidos o relación duplicada.'], 409);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear la relación D-D.', 'message' => $e->getMessage()], 500);
        }
    }

    // Omitimos show, update y destroy basados en un ID simple. 
    // Se haría una función personalizada para buscar/eliminar por IDs compuestos.

    /**
     * Remove a specific resource.
     * DELETE /api/relaciones/dd/{id_destino_origen}/{id_destino_relacionado}
     * (Requiere una ruta no-resource)
     */
    public function destroyByKeys(string $origenId, string $relacionadoId)
    {
        try {
            $deleted = RelacionDD::where([
                'id_destino_origen' => $origenId,
                'id_destino_relacionado' => $relacionadoId
            ])->delete();

            if ($deleted === 0) {
                return response()->json(['error' => 'Relación D-D no encontrada para eliminar.'], 404);
            }

            return response()->json(null, 204);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar la relación D-D.', 'message' => $e->getMessage()], 500);
        }
    }
}
