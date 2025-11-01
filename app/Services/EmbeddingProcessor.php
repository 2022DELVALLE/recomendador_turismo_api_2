<?php

namespace App\Services;

use App\Models\Embedding;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\InteraccionUC;

class EmbeddingProcessor
{
    // ⚠️ ¡AJUSTA ESTAS TRES LÍNEAS CON TUS RUTAS REALES! ⚠️
    private $NOMBRE_ENTORNO = 'tarma_ai';
    private $RUTA_MINICONDA_BASE = 'C:\Users\KENYO\miniconda3';
    private $RUTA_SCRIPT_PYTHON = 'C:\laragon\www\prueba_devue\recomendador_turismo_api\scripts\generar_embedding.py';
    // --------------------------------------------------------

    /**
     * Ejecuta el script Python para generar U^0 y W_UC.
     */
    private function ejecutarScriptPython(string $texto_usuario, array $vector_c0_real): ?array
    {
        $arg_texto = escapeshellarg($texto_usuario);
        $arg_vector_c0 = escapeshellarg(json_encode($vector_c0_real));

        $RUTA_PYTHON_ENV = "{$this->RUTA_MINICONDA_BASE}\\envs\\{$this->NOMBRE_ENTORNO}\\python.exe";
        // 1. Comando Python con comillas en las rutas
        $comando_python = "\"{$RUTA_PYTHON_ENV}\" \"{$this->RUTA_SCRIPT_PYTHON}\" {$arg_texto} {$arg_vector_c0}";

        // 2. Encapsulamos en CMD /C para robustez en Windows y redirigimos errores (2>&1)
        $comando = "cmd /C \"{$comando_python}\" 2>&1";

        // Logueamos el comando completo para depuración
        Log::info("Comando Python a ejecutar: " . $comando);

        $output = shell_exec($comando);
        $resultado = json_decode($output, true);

        if ($resultado && isset($resultado['U0_vector']) && isset($resultado['WUC_peso'])) {
            return [
                'U0_vector_json' => json_encode($resultado['U0_vector']),
                'WUC_peso' => $resultado['WUC_peso']
            ];
        }

        Log::error("Fallo de Script Python. Comando: {$comando}. Salida: " . $output);
        return null;
    }

    /**
     * Orquesta el Paso 5: Genera U^0, guarda U^0 en Embeddings y actualiza W_UC en Interacción.
     */
    public function procesarRegistro(int $id_usuario, int $id_contexto, string $texto_usuario, array $vector_c0_real): bool
    {
        try {
            // 5.1: Ejecución del Script Python
            $datosEmbedding = $this->ejecutarScriptPython($texto_usuario, $vector_c0_real);

            if (!$datosEmbedding) {
                return false; // El script Python falló
            }

            // 5.2: Guardado de U^0 (INSERT en Embeddings)
            Embedding::create([
                'tipo_nodo' => 'U',
                'id_referencia' => $id_usuario,
                'vector_embedding' => $datosEmbedding['U0_vector_json'],
                'fecha_generacion' => now(),
            ]);

            // 5.3: Guardado de W_UC (UPDATE en Interaccion_Usuario_Contexto)
            InteraccionUC::where('id_usuario', $id_usuario)
                ->where('id_contexto', $id_contexto)
                ->update(['peso_uc' => $datosEmbedding['WUC_peso']]);

            return true; // Éxito
        } catch (Exception $e) {
            Log::error("Error en EmbeddingProcessor para Usuario ID {$id_usuario}: " . $e->getMessage());
            return false;
        }
    }
}
