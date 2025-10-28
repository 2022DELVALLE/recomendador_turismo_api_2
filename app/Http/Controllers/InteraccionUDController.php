<?php

namespace App\Http\Controllers;

use App\Models\InteraccionUD;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

class InteraccionUDController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/interacciones/ud
     */
    public function index()
    {
        try {
            $interacciones = InteraccionUD::with(['usuario', 'destino'])->get();
            return response()->json($interacciones, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener las interacciones U-D.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/interacciones/ud
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'id_usuario' => 'required|exists:usuarios,id_usuario',
                'id_destino' => 'required|exists:destinos,id_destino',
                'rating' => 'required|numeric|min:1|max:5',
                'fecha_visita' => 'required|date',
                // Validación para clave única compuesta
                'id_usuario' => 'unique:interaccion_usuario_destino,id_usuario,NULL,id_interaccion,id_destino,' . $request->id_destino,
            ]);

            $interaccion = InteraccionUD::create($request->all());

            return response()->json($interaccion, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para la interacción.', 'messages' => $e->errors()], 422);
        } catch (QueryException $e) {
            // 23000: Foreign Key violation o Unique constraint violation
            return response()->json(['error' => 'Error de base de datos. Verifique IDs de usuario/destino o interacción duplicada.'], 409);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear la interacción U-D.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/interacciones/ud/{id_interaccion}
     */
    public function show(string $id)
    {
        try {
            $interaccion = InteraccionUD::with(['usuario', 'destino'])->findOrFail($id);
            return response()->json($interaccion, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Interacción U-D no encontrada.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener la interacción U-D.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/interacciones/ud/{id_interaccion}
     */
    public function destroy(string $id)
    {
        try {
            $interaccion = InteraccionUD::findOrFail($id);
            $interaccion->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Interacción U-D no encontrada para eliminar.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar la interacción U-D.', 'message' => $e->getMessage()], 500);
        }
    }

    // El método update se omite o se maneja si es estrictamente necesario,
    // ya que las interacciones suelen registrarse como eventos inmutables.
}
