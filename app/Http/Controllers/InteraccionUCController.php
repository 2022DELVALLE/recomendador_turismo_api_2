<?php

namespace App\Http\Controllers;

use App\Models\InteraccionUC;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

class InteraccionUCController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/interacciones/uc
     */
    public function index()
    {
        try {
            $interacciones = InteraccionUC::with(['usuario', 'contexto'])->get();
            return response()->json($interacciones, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener las interacciones U-C.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/interacciones/uc
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_usuario' => 'required|exists:usuarios,id_usuario',
                'id_contexto' => 'required|exists:contextos,id_contexto',
                'servicios_utilizados' => 'required|boolean',
                // Validación para clave única compuesta
                'id_usuario' => 'unique:interaccion_usuario_contexto,id_usuario,NULL,id_uc,id_contexto,' . $request->id_contexto,
            ]);

            $interaccion = InteraccionUC::create($request->all());

            return response()->json($interaccion, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para la interacción U-C.', 'messages' => $e->errors()], 422);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos. Verifique IDs de usuario/contexto o interacción duplicada.'], 409);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear la interacción U-C.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/interacciones/uc/{id_uc}
     */
    public function show(string $id)
    {
        try {
            $interaccion = InteraccionUC::with(['usuario', 'contexto'])->findOrFail($id);
            return response()->json($interaccion, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Interacción U-C no encontrada.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener la interacción U-C.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/interacciones/uc/{id_uc}
     */
    public function destroy(string $id)
    {
        try {
            $interaccion = InteraccionUC::findOrFail($id);
            $interaccion->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Interacción U-C no encontrada para eliminar.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar la interacción U-C.', 'message' => $e->getMessage()], 500);
        }
    }
    // Update no incluido, interacciones son eventos.
}
