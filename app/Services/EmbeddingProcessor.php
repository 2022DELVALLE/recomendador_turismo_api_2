<?php

namespace App\Services;

use App\Models\Embedding;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\InteraccionUC;

class EmbeddingProcessor
{
    private $NOMBRE_ENTORNO = 'tarma_ai';
    private $RUTA_MINICONDA_BASE = 'C:\Users\KENYO\miniconda3';
    private $RUTA_SCRIPT_PYTHON = 'C:\laragon\www\prueba_devue\recomendador_turismo_api\scripts\generar_embedding.py';

    /**
     * Ejecuta el script Python usando archivos temporales para evitar límites de CMD.
     */
    private function ejecutarScriptPython(string $texto_usuario, array $vector_c0_real): ?array
    {
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
