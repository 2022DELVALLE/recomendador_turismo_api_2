<?php

namespace App\Http\Controllers;

use App\Models\Contexto;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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

    /**
     * Obtiene el contexto actual (Clima y Hora) para Tarma.
     * Implementa una caché de 10 minutos para cumplir con el plan gratuito de OpenWeather.
     * GET /api/contexto/actual
     */
    public function obtenerContextoActual(Request $request)
    {
        // 🚨 CORRECCIÓN: Usamos el ID de Tarma encontrado (3927758) para la clave de caché.
        $cacheKey = 'tarma_weather_context_3927758';
        $cacheDuration = 600; // 10 minutos * 60 segundos/minuto

        // --- 1. INTENTAR OBTENER DE CACHÉ (LÍMITE DE 10 MINUTOS) ---
        if (Cache::has($cacheKey)) {
            Log::info('Contexto obtenido de la caché (menos de 10 minutos).');
            return response()->json(Cache::get($cacheKey), 200);
        }

        try {
            // --- 2. Preparación para la Llamada a la API (Solo si no está cacheado) ---
            $apiKey = env('OPENWEATHER_API_KEY');
            // 🚨 CORRECCIÓN: La URL base en .env debe ser: https://api.openweathermap.org/data/2.5/weather
            $baseUrl = env('OPENWEATHER_BASE_URL');
            // 🚨 CORRECCIÓN: Usamos el ID de Tarma encontrado.
            $cityId = env('OPENWEATHER_CITY_ID', '3927758');

            if (!$apiKey) {
                Log::warning("Falta OPENWEATHER_API_KEY en .env. Usando Fallback.");
                return $this->getFallbackContext();
            }

            // Llamada a la API de clima actual (weather) usando 'id'
            $response = Http::get($baseUrl, [
                'id' => $cityId,
                'appid' => $apiKey,
                'units' => 'metric',
                'lang' => 'es'
            ]);

            // Manejo de la respuesta
            if ($response->successful()) {
                $data = $response->json();

                // 🚨 CORRECCIÓN: El endpoint /weather devuelve directamente el objeto, no una lista.
                // Se extrae directamente sin usar $data['list'][0].
                $clima_actual = $data['weather'][0]['description'] ?? 'Desconocido';
                $temperatura = $data['main']['temp'] ?? null;

                if (is_null($temperatura)) {
                    Log::warning("Respuesta de OpenWeather sin datos de temperatura.");
                    return $this->getFallbackContext();
                }
            } else {
                Log::error("Error al obtener el clima: " . $response->body());
                return $this->getFallbackContext();
            }

            // --- 3. Obtener Datos de Hora ---
            $now = now('America/Lima');
            $hora_actual = $now->hour;
            $momento_del_dia = ($hora_actual >= 6 && $hora_actual < 19) ? 'Día' : 'Noche';

            // --- 4. Preparar la Respuesta, Cachear y Devolver ---
            $contextoCompleto = [
                'clima_actual' => ucfirst($clima_actual),
                'temperatura_c' => $temperatura,
                'momento_del_dia' => $momento_del_dia,
                'hora_ejecucion' => $now->toTimeString(),
            ];

            // Almacenar en caché por 10 minutos (600 segundos) antes de devolver
            Cache::put($cacheKey, $contextoCompleto, $cacheDuration);
            Log::info('Nuevo contexto obtenido de la API y cacheado por 10 minutos.');

            return response()->json($contextoCompleto, 200);
        } catch (Exception $e) {
            Log::error("Excepción en obtenerContextoActual: " . $e->getMessage());
            return $this->getFallbackContext();
        }
    }

    /**
     * Devuelve un contexto predeterminado en caso de fallo de la API.
     */
    private function getFallbackContext(): \Illuminate\Http\JsonResponse
    {
        $now = now('America/Lima');
        $hora_actual = $now->hour;
        $momento_del_dia = ($hora_actual >= 6 && $hora_actual < 19) ? 'Día' : 'Noche';

        return response()->json([
            'clima_actual' => 'Templado y Nublado (Fallback)',
            'temperatura_c' => 15,
            'momento_del_dia' => $momento_del_dia,
            'hora_ejecucion' => $now->toTimeString(),
            'error' => 'No se pudo obtener el contexto en tiempo real. Usando valores predeterminados.'
        ], 503);
    }
}
