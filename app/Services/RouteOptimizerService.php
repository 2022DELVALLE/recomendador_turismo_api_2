<?php

namespace App\Services;

use App\Models\Destino; // Asumiendo que ya tienes un modelo Destino
use App\Models\PrediccionRating;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\EmbeddingController; // Necesario para la inyección

class RouteOptimizerService
{
    protected $embeddingController;
    private const THEMES = ['Cultural', 'Natural', 'Gastronómica'];
    private const AFFINITY_BASE = 0.50;
    private const CLIMATE_BASE = 0.30;
    private const DISTANCE_BASE = 0.20;

    public function __construct(EmbeddingController $embeddingController)
    {
        // Inyección del controlador existente para usar su lógica de afinidad (Similitud Coseno)
        $this->embeddingController = $embeddingController;
    }

    /**
     * B3.4: Orquesta la predicción, el registro y la optimización para generar las 3 rutas.
     * @param int $userId ID del usuario autenticado.
     * @param array $filters Filtros de optimización recibidos (ej: ['clima', 'costo']).
     * @return array Las 3 rutas sugeridas (Cultural, Natural, Gastronómica).
     */
    public function generateOptimizedRoutes(int $userId, array $filters = []): array
    {
        // 1. Ejecución de la Inferencia/Afinidad (Simula B3.1)
        // Obtiene el ranking de sugerencias Top-N (Similitud Coseno)
        $inferenceResponse = $this->embeddingController->propagateAndAggregate($userId, $save_new_embedding = false);

        $inferenceData = json_decode($inferenceResponse->getContent(), true);

        if (empty($inferenceData['ranking_top_5_sugerencias'])) {
            Log::warning("No se generó ranking de sugerencias para el usuario {$userId}.");
            return [];
        }

        $affinityResults = $inferenceData['ranking_top_5_sugerencias'];

        // 2. Registro de Predicción (B3.3)
        $this->registerPredictedRatings($userId, $affinityResults);

        // 3. Optimización de Rutas (B3.2 y B3.6)
        $rutasOptimizadas = $this->optimizeRoutesByTheme($userId, $filters);

        return $rutasOptimizadas;
    }

    /**
     * B3.3: Convierte los resultados de la afinidad (Similitud Coseno) en Predicciones de Rating
     * e inserta los registros en la tabla Prediccion_Rating.
     */
    protected function registerPredictedRatings(int $userId, array $affinityResults): void
    {
        // Limpiar predicciones anteriores (solo se guarda la predicción más reciente)
        PrediccionRating::where('id_usuario', $userId)->delete();

        $dataToInsert = [];
        $modeloUsado = 'Similitud-Coseno';

        foreach ($affinityResults as $rec) {
            $dataToInsert[] = [
                'id_usuario' => $userId,

                // 🚨 CORRECCIÓN CLAVE: 
                // El EmbeddingController devuelve 'id_destino' después del enriquecimiento.
                // Antes usabas $rec['id_referencia'], lo que causaba el error.
                'id_destino' => $rec['id_destino'], // <--- CAMBIO CLAVE AQUÍ

                // El resto de los campos deberían coincidir con el array enriquecido:
                'rating_predicho' => $rec['similitud_coseno'], // Usamos la similitud como rating
                'fecha_prediccion' => now(),
                // ⭐⭐⭐ AÑADE ESTA LÍNEA ⭐⭐⭐
                'modelo_usado' => 'GNN-Similitud Coseno', // O un valor que describa tu modelo
            ];
        }

        if (!empty($dataToInsert)) {
            PrediccionRating::insert($dataToInsert);
        }
    }

    /**
     * B3.2 y B3.6: Agrupa los destinos predichos, calcula los pesos dinámicos y aplica la optimización TSP.
     */
    protected function optimizeRoutesByTheme(int $userId, array $filters = []): array
    {
        // B3.6: Calcular pesos dinámicos basados en los filtros (w1, w2, w3)
        $weights = $this->calculateDynamicWeights($filters);

        // 1. Obtener destinos con predicción (afinidad) y sus datos
        $predicciones = PrediccionRating::where('id_usuario', $userId)
            ->join('destinos', 'prediccion_rating.id_destino', '=', 'destinos.id_destino')
            ->select('destinos.*', 'prediccion_rating.rating_predicho')
            ->orderBy('rating_predicho', 'desc')
            ->get();

        if ($predicciones->isEmpty()) {
            return [];
        }

        // 2. Agrupar por Categoría (Cultural, Natural, Gastronómica)
        $grupos = $predicciones->groupBy('categoria');
        $rutas = [];

        foreach (self::THEMES as $theme) {
            if (isset($grupos[$theme])) {
                // Seleccionar el Top-5 con mejor predicción en esa categoría
                $candidatos = $grupos[$theme]->take(5)->toArray();

                // Aplicar optimización (TSP heurístico) usando los pesos dinámicos
                $rutaOptimizada = $this->runTSP($candidatos, $weights);

                $rutas[$theme] = [
                    'nombre' => "Ruta {$theme}",
                    'destinos_ordenados' => $rutaOptimizada,
                    'pesos_usados' => $weights,
                    'afinidad_total' => array_sum(array_column($rutaOptimizada, 'rating_predicho'))
                ];
            }
        }

        return $rutas;
    }

    /**
     * B3.6: Calcula los pesos de la función de optimización basado en filtros.
     * El total de pesos siempre suma ~1.0.
     */
    protected function calculateDynamicWeights(array $filters): array
    {
        $afinidad = self::AFFINITY_BASE;
        $clima = self::CLIMATE_BASE;
        $distancia = self::DISTANCE_BASE;

        // Asignar bonos de peso si el filtro está activo
        if (in_array('clima', $filters)) {
            $clima += 0.30;
            $afinidad = max(0.1, $afinidad - 0.20);
            $distancia = max(0.1, $distancia - 0.10);
        }
        // 'costo' y 'tiempo' se relacionan con la minimización de 'distancia' en el TSP
        if (in_array('costo', $filters) || in_array('tiempo', $filters)) {
            $distancia += 0.30;
            $afinidad = max(0.1, $afinidad - 0.20);
            $clima = max(0.1, $clima - 0.10);
        }

        // Normalizar los pesos para que sumen 1.0
        $total = $afinidad + $clima + $distancia;

        return [
            'afinidad' => round($afinidad / $total, 2),
            'clima' => round($clima / $total, 2),
            'distancia' => round($distancia / $total, 2)
        ];
    }

    /**
     * B3.2: Función de optimización TSP (simulada) que ordena los destinos.
     * Utiliza la heurística del vecino más cercano, ponderando la distancia.
     */
    protected function runTSP(array $destinos, array $weights): array
    {
        if (empty($destinos)) return [];

        // 1. Usar la afinidad predicha para decidir el punto de partida (mejor rating)
        usort($destinos, fn($a, $b) => $b['rating_predicho'] <=> $a['rating_predicho']);
        $startPoint = array_shift($destinos);
        $ruta = [$startPoint];
        $currentLocation = [$startPoint['latitud'], $startPoint['longitud']];

        // 2. Aplicar el bucle heurístico (vecino más cercano)
        while (!empty($destinos)) {
            $bestMatchIndex = -1;
            $minScore = INF;

            foreach ($destinos as $index => $destino) {
                $distance = $this->calculateDistance($currentLocation[0], $currentLocation[1], $destino['latitud'], $destino['longitud']);

                // Función de Costo Ponderada (TSP)
                // Score = (w_distancia * Distancia) - (w_afinidad * Afinidad) - (w_clima * Impacto_Clima)
                // El objetivo es MINIMIZAR el Score

                // NOTA: Para simular, usamos el 'rating_predicho' como Afinidad.
                // Y simplificamos el clima asumiendo que un rating más alto ya implica buen clima.
                $score = ($weights['distancia'] * $distance) - ($weights['afinidad'] * $destino['rating_predicho']);

                if ($score < $minScore) {
                    $minScore = $score;
                    $bestMatchIndex = $index;
                }
            }

            if ($bestMatchIndex !== -1) {
                $nextStop = array_splice($destinos, $bestMatchIndex, 1)[0];
                $ruta[] = $nextStop;
                $currentLocation = [$nextStop['latitud'], $nextStop['longitud']];
            } else {
                break;
            }
        }

        return $ruta;
    }

    /**
     * Helper: Calcula una distancia aproximada entre dos puntos (Euclidiana simple).
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Simulación de Distancia Euclidiana
        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;
        return sqrt($deltaLat * $deltaLat + $deltaLon * $deltaLon);
    }
}
