<?php

namespace App\Http\Controllers;

use App\Models\EventoFestividad;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Exception;

class EventoFestividadController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/eventos
     */
    public function index()
    {
        try {
            $eventos = EventoFestividad::with('destino')->get(); // Incluimos el Destino asociado
            return response()->json($eventos, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener los eventos.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/eventos
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre_evento' => 'required|string|max:100',
                'tipo_evento' => 'required|string|max:50',
                'fecha_inicio' => 'required|date',
                'lugar_asociado' => 'required|exists:destinos,id_destino', // Valida que el FK exista
            ]);

            $evento = EventoFestividad::create($request->all());
            
            return response()->json($evento, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para el evento.', 'messages' => $e->errors()], 422);
        } catch (QueryException $e) {
             // Este error captura problemas de FK no existentes
             return response()->json(['error' => 'Error de base de datos. Asegúrese que el ID de destino asociado exista.'], 409); // 409 Conflict
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear el evento.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/eventos/{id_evento}
     */
    public function show(string $id)
    {
        try {
            $evento = EventoFestividad::with('destino')->findOrFail($id);
            return response()->json($evento, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Evento no encontrado.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener el evento.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/eventos/{id_evento}
     */
    public function update(Request $request, string $id)
    {
        try {
            $evento = EventoFestividad::findOrFail($id);
            
            $request->validate([
                'nombre_evento' => 'sometimes|required|string|max:100',
                'lugar_asociado' => 'sometimes|required|exists:destinos,id_destino',
            ]);

            $evento->update($request->all());
            
            return response()->json($evento, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Evento no encontrado para actualizar.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de actualización inválidos para el evento.', 'messages' => $e->errors()], 422);
        } catch (QueryException $e) {
             return response()->json(['error' => 'Error de base de datos. Asegúrese que el ID de destino asociado exista.'], 409);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo actualizar el evento.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/eventos/{id_evento}
     */
    public function destroy(string $id)
    {
        try {
            $evento = EventoFestividad::findOrFail($id);
            $evento->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Evento no encontrado para eliminar.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar el evento.', 'message' => $e->getMessage()], 500);
        }
    }
}