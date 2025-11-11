<?php

namespace App\Services;

use App\Models\Embedding;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\InteraccionUC;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EmbeddingProcessor
{
    private $NOMBRE_ENTORNO = 'tarma_ai';
    private $RUTA_MINICONDA_BASE = 'C:\Users\KENYO\miniconda3';
    private $RUTA_SCRIPT_PYTHON = 'C:\laragon\www\prueba_devue\recomendador_turismo_api\scripts\generar_embedding.py';


    // Tasa de aprendizaje/ajuste (Alpha) para el ajuste online U0 -> U1
    // Un valor de 0.15 significa que la nueva interacción contribuye un 15% al nuevo vector.
    private const ALPHA = 0.15;

    /**
     * B2.5.3: Ajusta el embedding del usuario (U0) basado en los destinos visualizados (D_i).
     * Implementa la fórmula: U1 = (1 - alpha) * U0 + alpha * D_promedio
     *
     * @param int $user_id ID del usuario a actualizar.
     * @param array $destino_ids IDs de los destinos visualizados.
     * @return bool True si la actualización fue exitosa, False en caso de error.
     */
    public function ajustarEmbeddingPorVisualizacion(int $user_id, array $destino_ids): bool
    {
        // ... (Tu implementación existente para promediar múltiples destinos)
        // Lógica de promediado de vectores D_i y luego el ajuste con U0.
        try {
            // 1. Obtener U0: Embedding actual del usuario (tipo_nodo = 'U')
            $embeddingUsuario = Embedding::where('tipo_nodo', 'U')
                ->where('id_referencia', $user_id)
                ->firstOrFail(); // Lanza error si no existe

            $U0 = json_decode($embeddingUsuario->vector_embedding, true);
            $dimension = count($U0);

            // 2. Obtener D_visualizados: Embeddings de los destinos (tipo_nodo = 'P')
            $embeddingsDestinos = Embedding::where('tipo_nodo', 'P')
                ->whereIn('id_referencia', array_unique($destino_ids))
                ->get();

            if ($embeddingsDestinos->isEmpty()) {
                Log::warning("No se encontraron embeddings de Destino ('P') para los IDs proporcionados. El embedding del usuario U0 no se ajusta.");
                return true; // Se considera exitoso, pero sin ajuste
            }

            // 3. Calcular el Vector de Preferencia Promedio (D_promedio)
            $D_sum = array_fill(0, $dimension, 0.0);
            $D_count = 0;

            foreach ($embeddingsDestinos as $destino) {
                $Di = json_decode($destino->vector_embedding, true);

                if (count($Di) !== $dimension) {
                    Log::error("Dimensiones de vector inconsistentes entre U y D. Se omite destino: " . $destino->id_referencia);
                    continue;
                }

                // Suma de vectores de destinos
                for ($i = 0; $i < $dimension; $i++) {
                    $D_sum[$i] += $Di[$i];
                }
                $D_count++;
            }

            if ($D_count === 0) {
                return true;
            }

            // D_promedio = Suma / Cantidad
            $D_promedio = array_map(function ($sum) use ($D_count) {
                return $sum / $D_count;
            }, $D_sum);


            // 4. Calcular el Nuevo Vector U1 (Ajuste Ponderado)
            $U1 = [];
            $alpha = self::ALPHA;

            for ($i = 0; $i < $dimension; $i++) {
                // FÓRMULA: U1[i] = (1 - alpha) * U0[i] + alpha * D_promedio[i]
                $U1[$i] = (1 - $alpha) * $U0[$i] + $alpha * $D_promedio[$i];
            }

            // 5. Actualizar la Base de Datos con U1
            $embeddingUsuario->vector_embedding = json_encode($U1);
            $embeddingUsuario->fecha_generacion = now(); // Actualizar la fecha de última modificación
            $embeddingUsuario->save();

            Log::info("Embedding de usuario {$user_id} ajustado con éxito. Dims: {$dimension}, Dests: {$D_count}");

            return true;
        } catch (ModelNotFoundException $e) {
            Log::error("ERROR: Embedding de usuario {$user_id} (U) no encontrado para ajustar. Mensaje: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            Log::error("ERROR inesperado en el cálculo de ajuste vectorial: " . $e->getMessage());
            return false;
        }
    }


    /**
     * 💡 NUEVO MÉTODO (Tarea B3.3.4): Ajusta el embedding U0 del usuario basado en una ÚNICA interacción.
     * Implementa la fórmula: U1 = (1 - alpha) * U0 + alpha * D_i
     *
     * @param int $user_id ID del usuario a actualizar.
     * @param int $destino_id ID del destino con el que interactuó.
     * @param string $tipo_interaccion Tipo de interacción ('GUARDADO_RUTA', 'CLICK', etc.)
     * @return bool True si la actualización fue exitosa, False en caso de error.
     */
    public function ajustarEmbeddingPorInteraccionUnica(int $user_id, int $destino_id, string $tipo_interaccion): bool
    {
        // Opcionalmente, se podría aplicar un factor de ponderación (multiplier)
        // basado en el tipo_interaccion, pero usamos el ALPHA base por ahora.
        $alpha = self::ALPHA;

        try {
            // 1. Obtener U0: Embedding actual del usuario (tipo_nodo = 'U')
            $embeddingUsuario = Embedding::where('tipo_nodo', 'U')
                ->where('id_referencia', $user_id)
                ->firstOrFail();

            $U0 = json_decode($embeddingUsuario->vector_embedding, true);
            $dimension = count($U0);

            // 2. Obtener Di: Embedding del destino (tipo_nodo = 'P')
            $embeddingDestino = Embedding::where('tipo_nodo', 'P')
                ->where('id_referencia', $destino_id)
                ->first();

            if (!$embeddingDestino) {
                Log::warning("No se encontró embedding de Destino ('P') para ID {$destino_id}. No se ajusta U0.");
                return true;
            }

            $Di = json_decode($embeddingDestino->vector_embedding, true);

            if (count($Di) !== $dimension) {
                Log::error("Dimensiones de vector inconsistentes entre U y D. Se omite ajuste.");
                return false;
            }

            // 3. Calcular el Nuevo Vector U1 (Ajuste Ponderado)
            $U1 = [];
            for ($i = 0; $i < $dimension; $i++) {
                // FÓRMULA: U1[i] = (1 - alpha) * U0[i] + alpha * Di[i]
                $U1[$i] = (1 - $alpha) * $U0[$i] + $alpha * $Di[$i];
            }

            // 4. Actualizar la Base de Datos con U1
            $embeddingUsuario->vector_embedding = json_encode($U1);
            $embeddingUsuario->fecha_generacion = now();
            $embeddingUsuario->save();

            Log::info("Embedding de usuario {$user_id} ajustado por {$tipo_interaccion} con éxito.");

            return true;
        } catch (ModelNotFoundException $e) {
            Log::error("ERROR: Embedding de usuario {$user_id} (U) no encontrado para ajuste único. Mensaje: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            Log::error("ERROR inesperado en ajuste vectorial único: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Ejecuta el script Python usando archivos temporales para evitar límites de CMD.
     * ... (El resto de esta función no se modifica)
     */
    private function ejecutarScriptPython(string $texto_usuario, array $vector_c0_real): ?array
    {
        // ... (Tu implementación existente)
        $tempDir = storage_path('app/temp_embeddings');

        // Crear directorio si no existe
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Crear archivo temporal único
        $tempFile = $tempDir . '/input_' . uniqid() . '_' . time() . '.json';

        try {
            // Escribir datos al archivo temporal
            $inputData = [
                'texto' => $texto_usuario,
                'contexto_vector' => $vector_c0_real
            ];

            file_put_contents($tempFile, json_encode($inputData, JSON_UNESCAPED_UNICODE));

            if (!file_exists($tempFile)) {
                Log::error("No se pudo crear archivo temporal: {$tempFile}");
                return null;
            }

            $RUTA_PYTHON_ENV = "{$this->RUTA_MINICONDA_BASE}\\envs\\{$this->NOMBRE_ENTORNO}\\python.exe";

            // Comando simplificado usando solo la ruta del archivo
            $comando_python = "\"{$RUTA_PYTHON_ENV}\" \"{$this->RUTA_SCRIPT_PYTHON}\" \"{$tempFile}\"";
            $comando = "cmd /C \"{$comando_python}\" 2>&1";

            Log::info("Ejecutando Python con archivo temporal: {$tempFile}");
            Log::debug("Comando: {$comando}");

            // Ejecutar comando
            exec($comando, $output, $returnCode);

            // Unir salida en string
            $outputString = implode("\n", $output);

            Log::info("Código de retorno Python: {$returnCode}");
            Log::info("Salida Python: {$outputString}");

            // Verificar código de retorno
            if ($returnCode !== 0) {
                Log::error("Script Python falló con código {$returnCode}. Salida: {$outputString}");
                return null;
            }

            // Decodificar resultado
            $resultado = json_decode($outputString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Error decodificando JSON. Salida: {$outputString}");
                return null;
            }

            if ($resultado && isset($resultado['U0_vector']) && isset($resultado['WUC_peso'])) {
                return [
                    'U0_vector_json' => json_encode($resultado['U0_vector']),
                    'WUC_peso' => $resultado['WUC_peso']
                ];
            }

            Log::error("Respuesta Python incompleta: " . $outputString);
            return null;
        } catch (Exception $e) {
            Log::error("Excepción en ejecutarScriptPython: " . $e->getMessage());
            return null;
        } finally {
            // Limpiar archivo temporal
            if (file_exists($tempFile)) {
                @unlink($tempFile);
                Log::debug("Archivo temporal eliminado: {$tempFile}");
            }
        }
    }

    /**
     * Orquesta el Paso 5: Genera U^0, guarda U^0 en Embeddings y actualiza W_UC en Interacción.
     * ... (El resto de esta función no se modifica)
     */
    public function procesarRegistro(int $id_usuario, int $id_contexto, string $texto_usuario, array $vector_c0_real): bool
    {
        try {
            Log::info("Iniciando procesamiento embedding para Usuario ID: {$id_usuario}");

            // 5.1: Ejecución del Script Python
            $datosEmbedding = $this->ejecutarScriptPython($texto_usuario, $vector_c0_real);

            if (!$datosEmbedding) {
                Log::error("Script Python no retornó datos válidos para Usuario ID: {$id_usuario}");
                return false;
            }

            // 5.2: Guardado de U^0 (INSERT en Embeddings)
            Embedding::create([
                'tipo_nodo' => 'U',
                'id_referencia' => $id_usuario,
                'vector_embedding' => $datosEmbedding['U0_vector_json'],
                'fecha_generacion' => now(),
            ]);

            Log::info("Embedding U^0 guardado para Usuario ID: {$id_usuario}");

            // 5.3: Guardado de W_UC (UPDATE en Interaccion_Usuario_Contexto)
            $updated = InteraccionUC::where('id_usuario', $id_usuario)
                ->where('id_contexto', $id_contexto)
                ->update(['peso_uc' => $datosEmbedding['WUC_peso']]);

            if ($updated > 0) {
                Log::info("Peso W_UC actualizado para Usuario ID: {$id_usuario}, Contexto ID: {$id_contexto}");
            } else {
                Log::warning("No se encontró interacción UC para actualizar (Usuario: {$id_usuario}, Contexto: {$id_contexto})");
            }

            return true;
        } catch (Exception $e) {
            Log::error("Error en EmbeddingProcessor para Usuario ID {$id_usuario}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
}
