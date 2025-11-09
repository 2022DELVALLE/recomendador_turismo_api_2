<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DestinoController extends Controller
{
    /**
     * Tarea B3.1.1: Consulta inicial y filtrada de destinos, integrando contexto (Clima y Horario).
     * GET /api/explorar?filtros
     */
    public function explorarDestinos(Request $request)
    {
        try {
            // Seleccionamos los campos necesarios para la Tarjeta Atracción
            $query = Destino::select([
                'id_destino',
                'nombre_destino',
                'descripcion_corta',
                'categoria',
                'subcategoria',
                'costo_promedio',
                'dificultad_acceso',
                'compatibilidad_clima',
                'horario_relevancia', // Nuevo campo para filtrar por hora
                'foto_principal_url',
                'latitud',
                'longitud',
            ]);

            // --- Lógica de Filtrado Temático/Económico/Contextual ---

            // 1. Filtrar por Categoría
            if ($request->has('categoria') && $request->categoria !== 'Todas') {
                $query->where('categoria', $request->categoria);
            }

            // 2. Filtrar por Nivel de Gasto (Costo Promedio)
            if ($request->has('gasto')) {
                $query->where('costo_promedio', '<=', $request->gasto);
            }

            // 3. Filtrar por Dificultad
            if ($request->has('dificultad') && $request->dificultad !== 'Todas') {
                $query->where('dificultad_acceso', $request->dificultad);
            }

            // 4. FILTRO CONTEXTUAL POR HORARIO (Momento del Día: Día/Noche)
            if ($request->has('momento_dia') && $request->momento_dia) {
                $momento = $request->momento_dia; // "Día" o "Noche"
                // Muestra destinos marcados para el momento actual O marcados como 'Ambos'
                $query->where(function ($q) use ($momento) {
                    $q->where('horario_relevancia', $momento)
                        ->orWhere('horario_relevancia', 'Ambos');
                });
            }

            // 5. FILTRO CONTEXTUAL POR CLIMA
            if ($request->has('clima_actual') && $request->clima_actual) {
                $clima = $request->clima_actual;
                // Busca si el string del clima actual se encuentra dentro del array JSON 'compatibilidad_clima'
                // Esto es crucial si la columna 'compatibilidad_clima' es de tipo JSON
                $query->whereJsonContains('compatibilidad_clima', $clima);
            }


            // --- Lógica de Distancia (Geográfica) ---

            if ($request->has(['distancia_max', 'user_lat', 'user_lon'])) {
                $maxDistance = $request->distancia_max; // Distancia en KM
                $userLat = $request->user_lat;
                $userLon = $request->user_lon;

                // Construcción de la cadena SQL de la fórmula Haversine (Solución al error anterior)
                $distanceSql = "(6371 * acos(cos(radians($userLat)) 
                    * cos(radians(latitud)) 
                    * cos(radians(longitud) - radians($userLon)) 
                    + sin(radians($userLat)) 
                    * sin(radians(latitud)))) AS distancia_km";

                // Añadir la distancia a los campos seleccionados
                $query->selectRaw($distanceSql);

                // Filtrar por la distancia calculada usando HAVING
                $query->having('distancia_km', '<=', $maxDistance);

                // Opcional: Ordenar por distancia (los más cercanos primero)
                $query->orderBy('distancia_km');
            }

            // Ejecutar la consulta
            $destinos = $query->limit(25)->get(); // Limitar la respuesta

            // --- Formateo de Salida para el Frontend ---
            $destinos->map(function ($destino) {
                // Mapear compatibilidad_clima a un campo simple 'clima_recomendado'
                $climaArray = is_array($destino->compatibilidad_clima)
                    ? $destino->compatibilidad_clima
                    : json_decode($destino->compatibilidad_clima, true);

                // Usar el primer clima compatible como el recomendado para la tarjeta, o 'N/A'
                $destino->clima_recomendado = (!empty($climaArray) && is_array($climaArray)) ? $climaArray[0] : 'N/A';

                // Opcional: Remover campos crudos para limpiar el payload
                unset($destino->compatibilidad_clima);
                unset($destino->horario_relevancia); // Ya se usó para filtrar
                return $destino;
            });

            return response()->json($destinos, 200);
        } catch (Exception $e) {
            Log::error("Error en explorarDestinos: " . $e->getMessage());
            return response()->json([
                'error' => 'No se pudieron obtener los destinos filtrados.',
                'message' => 'Error interno al procesar la consulta.'
            ], 500);
        }
    }
    // =========================================================
    // MÉTODOS CRUD EXISTENTES (Se mantienen sin cambios)
    // =========================================================

    /**
     * Display a listing of the resource.
     * GET /api/destinos
     */
    public function index()
    {
        try {
            $destinos = Destino::all();
            return response()->json($destinos, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener los destinos.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/destinos
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'nombre_destino' => 'required|string|max:100',
                'categoria' => 'required|string|max:50',
                'latitud' => 'required|numeric',
                'longitud' => 'required|numeric',
            ]);

            $destino = Destino::create($request->all());

            return response()->json($destino, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de entrada inválidos para el destino.', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo crear el destino.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/destinos/{id_destino}
     */
    public function show(string $id)
    {
        try {
            $destino = Destino::findOrFail($id);
            return response()->json($destino, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Destino no encontrado.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener el destino.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/destinos/{id_destino}
     */
    public function update(Request $request, string $id)
    {
        try {
            $destino = Destino::findOrFail($id);

            $request->validate([
                'nombre_destino' => 'sometimes|required|string|max:100',
                'categoria' => 'sometimes|required|string|max:50',
            ]);

            $destino->update($request->all());

            return response()->json($destino, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Destino no encontrado para actualizar.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Datos de actualización inválidos para el destino.', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo actualizar el destino.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/destinos/{id_destino}
     */
    public function destroy(string $id)
    {
        try {
            $destino = Destino::findOrFail($id);
            $destino->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Destino no encontrado para eliminar.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo eliminar el destino.', 'message' => $e->getMessage()], 500);
        }
    }
}
