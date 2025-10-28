<?php

namespace App\Http\Controllers;

use App\Models\InteraccionUE;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

class InteraccionUEController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/interacciones/ue
     */
    public function index()
    {
        try {
            $interacciones = InteraccionUE::with(['usuario', 'evento'])->get();
            return response()->json($interacciones, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener las interacciones U-E.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/interacciones/ue
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_usuario' => 'required|exists:usuarios,id_usuario',
                'id_evento' => 'required|exists:evento_festividades,id_evento',
                'asistencia' => 'required|boolean',
                'fecha_participacion' => 'required|date',
                // Validación para clave única compuesta (U-E)
                'id_usuario' => 'unique:interaccion_usuario_evento,id_usuario,NULL,id_ue,id_evento,' . $request->id_evento,
            ]);

            $interaccion = InteraccionUE::create($request->all());

            return response()->json($interaccion, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para la interacción U-E.', 'messages' => $e->errors()], 422);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos. Verifique IDs o interacción duplicada.'], 409);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear la interacción U-E.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/interacciones/ue/{id_ue}
     */
    public function show(string $id)
    {
        try {
            $interaccion = InteraccionUE::with(['usuario', 'evento'])->findOrFail($id);
            return response()->json($interaccion, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Interacción U-E no encontrada.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener la interacción U-E.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/interacciones/ue/{id_ue}
     */
    public function destroy(string $id)
    {
        try {
            $interaccion = InteraccionUE::findOrFail($id);
            $interaccion->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Interacción U-E no encontrada para eliminar.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar la interacción U-E.', 'message' => $e->getMessage()], 500);
        }
    }
}
