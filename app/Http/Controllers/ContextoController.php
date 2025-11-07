<?php

namespace App\Http\Controllers;

use App\Models\Contexto;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\TransporteDisponible;

class ContextoController extends Controller
{
    // Clave para la caché
    private const CLIMATE_CACHE_KEY = 'tarma_weather_context_3927758';
    private const CLIMATE_CACHE_TTL = 600; // 10 minutos

    // Métodos existentes (index, store, show, update, destroy) - Se mantienen sin cambios

    public function index()
    {
        try {
            $contextos = Contexto::all();
            return response()->json($contextos, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener los contextos.', 'message' => $e->getMessage()], 500);
        }
    }

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
     * Obtiene el contexto actual (Clima, Hora y TRANSPORTE) para Tarma.
     * GET /api/contexto/actual
     */
    public function obtenerContextoActual(Request $request)
    {
        $cacheKey = self::CLIMATE_CACHE_KEY;
        $cacheDuration = self::CLIMATE_CACHE_TTL;

        // --- 1. INTENTAR OBTENER DE CACHÉ (LÍMITE DE 10 MINUTOS) ---
        if (Cache::has($cacheKey)) {
            Log::info('Contexto obtenido de la caché (menos de 10 minutos).');
            return response()->json(Cache::get($cacheKey), 200);
        }

        try {
            // --- 2. Obtener Datos de Clima ---
            $climaData = $this->llamarApiClima();

            // --- 3. Obtener Datos de Hora ---
            $now = now('America/Lima');
            $hora_actual = $now->hour;
            $momento_del_dia = ($hora_actual >= 6 && $hora_actual < 19) ? 'Día' : 'Noche';

            // --- 4. Obtener Datos de Transporte (IMPLEMENTACIÓN B2.2.3) ---
            $transporteData = $this->obtenerOpcionesTransporte();

            // --- 5. Preparar la Respuesta, Cachear y Devolver ---
            $contextoCompleto = array_merge($climaData, $transporteData, [
                'momento_del_dia' => $momento_del_dia,
                'hora_ejecucion' => $now->toTimeString(),
            ]);

            // Almacenar en caché por 10 minutos (600 segundos) antes de devolver
            Cache::put($cacheKey, $contextoCompleto, $cacheDuration);
            Log::info('Nuevo contexto obtenido de la API/DB y cacheado por 10 minutos.');

            return response()->json($contextoCompleto, 200);
        } catch (Exception $e) {
            Log::error("Excepción en obtenerContextoActual: " . $e->getMessage());
            return $this->getFallbackContext();
        }
    }

    /**
     * Consulta la base de datos para obtener las opciones de transporte activas (B2.2.3).
     * @return array
     */
    private function obtenerOpcionesTransporte(): array
    {
        try {
            // Consulta solo los transportes que están activos
            $transportesActivos = TransporteDisponible::where('activo', true)
                ->get(['tipo_transporte', 'costo_base_minimo', 'horario_disponibilidad'])
                ->toArray();
        } catch (Exception $e) {
            Log::error("Error al obtener transporte de la DB: " . $e->getMessage());
            // Devuelve un array vacío si la DB falla para no bloquear el proceso
            $transportesActivos = [];
        }

        return [
            'transporte_disponible' => $transportesActivos
        ];
    }

    /**
     * Simula la llamada a la API de clima (anteriormente en obtenerContextoActual).
     * @return array
     */
    private function llamarApiClima(): array
    {
        $apiKey = env('OPENWEATHER_API_KEY');
        $baseUrl = env('OPENWEATHER_BASE_URL');
        $cityId = env('OPENWEATHER_CITY_ID', '3927758');

        // Simulación: Si falta la clave API, se salta la llamada y se usa un dato seguro.
        if (!$apiKey) {
            Log::warning("Falta OPENWEATHER_API_KEY. Usando dato de clima seguro.");
            return [
                'clima_actual' => 'Nublado',
                'temperatura_c' => 16.0,
            ];
        }

        try {
            $response = Http::get($baseUrl, [
                'id' => $cityId,
                'appid' => $apiKey,
                'units' => 'metric',
                'lang' => 'es'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $clima_actual = $data['weather'][0]['description'] ?? 'Desconocido';
                $temperatura = $data['main']['temp'] ?? 15.0;

                return [
                    'clima_actual' => ucfirst($clima_actual),
                    'temperatura_c' => $temperatura,
                ];
            } else {
                Log::error("Error HTTP OpenWeather: " . $response->body());
                throw new Exception("Error en API de Clima.");
            }
        } catch (Exception $e) {
            Log::error("Fallo la llamada a la API de Clima: " . $e->getMessage());
            // Lanzamos una excepción para que el catch principal active el Fallback completo
            throw $e;
        }
    }

    /**
     * Devuelve un contexto predeterminado en caso de fallo de la API o DB.
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
            'transporte_disponible' => [ // <--- Transporte de Fallback
                [
                    'tipo_transporte' => 'Taxi Urbano',
                    'costo_base_minimo' => 5.00,
                    'horario_disponibilidad' => '24/7'
                ]
            ],
            'error' => 'No se pudo obtener el contexto en tiempo real. Usando valores predeterminados.'
        ], 503);
    }
}
