<?php
// CÓDIGO INICIA AQUÍ

namespace App\Http\Controllers;

use App\Models\RutaGuardada;
use App\Services\RouteOptimizerService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RouteController extends Controller
{
    protected $routeOptimizerService;

    /**
     * Inyección de dependencia del servicio optimizador.
     */
    public function __construct(RouteOptimizerService $routeOptimizerService)
    {
        $this->routeOptimizerService = $routeOptimizerService;
    }

    /**
     * B3.4: Endpoint para obtener las rutas inteligentes sugeridas.
     * POST /api/rutas/sugeridas
     */
    public function getSuggestedRoutes(Request $request)
    {
        // 1. Validar parámetros esenciales
        $request->validate([
            'id_usuario' => 'required|integer|exists:usuarios,id_usuario',
            'filtros_optimizacion' => 'nullable|array',
        ]);

        $userId = $request->input('id_usuario');
        $optimizationFilters = $request->input('filtros_optimizacion', []);

        Log::info("Solicitud de rutas inteligentes iniciada para Usuario ID: {$userId}");

        try {
            // 2. Delegar la orquestación al servicio (B3.1, B3.3, B3.2, B3.6)
            $suggestedRoutes = $this->routeOptimizerService->generateOptimizedRoutes($userId, $optimizationFilters);

            if (empty($suggestedRoutes)) {
                return response()->json([
                    'message' => 'No se encontraron destinos o afinidades suficientes para generar rutas inteligentes.',
                    'rutas' => [],
                ], 200);
            }

            // 3. Devolver el resultado
            return response()->json([
                'message' => 'Rutas inteligentes generadas y optimizadas con éxito.',
                'rutas' => $suggestedRoutes,
            ], 200);
        } catch (Exception $e) {
            Log::error("Error al generar rutas para Usuario ID {$userId}: " . $e->getMessage());
            return response()->json([
                'error' => 'Error interno al procesar la generación de rutas.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * B3.5: Endpoint para que el usuario guarde una de las rutas optimizadas sugeridas.
     * POST /api/rutas/guardar
     */
    public function saveRoute(Request $request)
    {
        // 1. Validación de la solicitud
        $request->validate([
            'id_usuario' => 'required|integer',
            'nombre_ruta' => 'required|string|max:100',
            'destinos_ordenados' => 'required|array|min:1',
            'afinidad_total' => 'required|numeric',
            'pesos_usados' => 'nullable|array',
        ]);

        $userId = $request->input('id_usuario');

        try {
            // 2. Preparación de datos
            $rutaData = [
                'id_usuario' => $userId,
                'nombre_ruta' => $request->input('nombre_ruta'),
                // NOTA: Usamos json_encode() para guardar el array como JSON en la base de datos
                'destinos_json' => json_encode($request->input('destinos_ordenados')), 
                'afinidad_total' => $request->input('afinidad_total'),
                'filtros_aplicados' => json_encode($request->input('pesos_usados') ?? []),
                'fecha_guardado' => now(),
            ];

            // 3. Guardar en la Base de Datos (usando el modelo RutaGuardada)
            $rutaGuardada = RutaGuardada::create($rutaData);

            Log::info("Ruta ID {$rutaGuardada->id_ruta_guardada} guardada por Usuario ID: {$userId}");

            // 4. Retorno de la respuesta
            return response()->json([
                'message' => 'Ruta guardada con éxito.',
                'id_ruta_guardada' => $rutaGuardada->id_ruta_guardada,
                'nombre' => $rutaGuardada->nombre_ruta,
            ], 201); // Código 201: Creado

        } catch (\Exception $e) {
            Log::error("Error al guardar ruta para Usuario ID {$userId}: " . $e->getMessage());
            return response()->json([
                'error' => 'No se pudo guardar la ruta.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}