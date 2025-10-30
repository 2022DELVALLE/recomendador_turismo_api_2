<?php

namespace App\Http\Controllers;

use App\Models\Contexto;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class ContextoController extends Controller
{
    /**
     * MÉTODOS EXISTENTES (index, store, show, update, destroy)
     * ... (Tu código existente aquí) ...
     */

    /**
     * Display a listing of the resource.
     * GET /api/contextos
     */
    public function index()
    {
        try {
            $contextos = Contexto::all();
            return response()->json($contextos, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener los contextos.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/contextos
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'clima_actual' => 'required|string|max:30',
                'temperatura_promedio' => 'required|numeric',
                'temporada' => 'required|string|max:30',
                'servicios_disponibles' => 'required|boolean',
            ]);

            $contexto = Contexto::create($request->all());

            return response()->json($contexto, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para el contexto.', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear el contexto.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/contextos/{id_contexto}
     */
    public function show(string $id)
    {
        try {
            $contexto = Contexto::findOrFail($id);
            return response()->json($contexto, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Contexto no encontrado.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener el contexto.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/contextos/{id_contexto}
     */
    public function update(Request $request, string $id)
    {
        try {
            $contexto = Contexto::findOrFail($id);

            $request->validate([
                'clima_actual' => 'sometimes|required|string|max:30',
                'temperatura_promedio' => 'sometimes|required|numeric',
            ]);

            $contexto->update($request->all());

            return response()->json($contexto, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Contexto no encontrado para actualizar.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de actualización inválidos para el contexto.', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo actualizar el contexto.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/contextos/{id_contexto}
     */
    public function destroy(string $id)
    {
        try {
            $contexto = Contexto::findOrFail($id);
            $contexto->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Contexto no encontrado para eliminar.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar el contexto.', 'message' => $e->getMessage()], 500);
        }
    }


    // =================================================================
    // NUEVO MÉTODO REQUERIDO: OBTENER EL NODO CONTEXTO ACTUAL (C)
    // =================================================================

    /**
     * Obtiene el ID del nodo de Contexto (C) más relevante en el momento actual.
     * GET /api/contexto/actual
     *
     * La lógica aquí es simular o buscar el 'escenario' de contexto que mejor
     * representa las condiciones ambientales y de servicio de Tarma ahora.
     */
    public function obtenerContextoActual(Request $request)
    {
        try {
            // 1. Simulación de la obtención de variables ambientales en tiempo real
            // NOTA: En un sistema real, esto podría ser una llamada a una API de clima.
            $condicionesActuales = [
                'clima_actual' => 'Templado', // Ejemplo basado en la temporada.
                'temporada' => 'Seca',
                'servicios_disponibles' => true, // La infraestructura general está operativa.
            ];

            // 2. Lógica de Matching (Búsqueda del nodo C más relevante)
            // Aquí, buscamos el registro de Contexto que coincide con estas condiciones.
            $contextoMatch = Contexto::query()
                ->where('clima_actual', $condicionesActuales['clima_actual'])
                ->where('temporada', $condicionesActuales['temporada'])
                // Opcionalmente, se pueden usar comparaciones de temperatura, etc.
                ->first(); // Tomamos el primer contexto que cumpla la condición.

            if (!$contextoMatch) {
                // Si no hay un contexto predefinido que coincida, se devuelve un contexto de fallback (id=1, por ejemplo).
                // Es crucial devolver un ID para poder crear el enlace U-C.
                $contextoMatch = Contexto::find(1);

                if (!$contextoMatch) {
                    return response()->json([
                        'error' => 'Contexto por defecto no encontrado. Asegúrese de tener al menos un registro en la tabla contextos.',
                        'request' => $condicionesActuales
                    ], 404);
                }
            }

            // 3. Respuesta
            // Devolvemos el contexto encontrado (incluye el id_contexto)
            return response()->json($contextoMatch, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error de servidor al buscar el contexto actual.', 'message' => $e->getMessage()], 500);
        }
    }
}
