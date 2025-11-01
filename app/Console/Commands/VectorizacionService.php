<?php

namespace App\Services;

use App\Models\Embedding;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Clase de servicio para manejar la lógica de shell y la ejecución
 * del script de Python para la vectorización de cualquier texto.
 * Se utiliza para la generación de vectores dinámicos (como R^0)
 * y para la lógica de actualización de perfiles (U+).
 */
class VectorizacionService
{
    /**
     * Ruta al script de Python para vectorización.
     * ¡AJUSTA ESTAS RUTAS SI ES NECESARIO! (Usamos el mismo script de vectorización para todos los textos)
     * @var string
     */
    private $RUTA_SCRIPT_PYTHON = 'C:\laragon\www\prueba_devue\recomendador_turismo_api\scripts\vectorizar_contexto.py';
    private $RUTA_MINICONDA_BASE = 'C:\Users\KENYO\miniconda3';
    private $NOMBRE_ENTORNO = 'tarma_ai';

    /**
     * Llama al script de Python para generar un vector de embedding (ej. R^0).
     *
     * @param string $texto El texto a vectorizar (ej. ReseñaTexto->contenido_original).
     * @return string|null El vector JSON resultante o null si falla.
     */
    public function ejecutarVectorizacionPython(string $texto): ?string
    {
        if (empty($texto)) {
            Log::warning("Vectorización de texto vacía solicitada.");
            return null;
        }

        $arg_texto = escapeshellarg($texto);

        // Define la ruta completa al ejecutable de Python dentro del entorno Conda
        $RUTA_PYTHON_ENV = "{$this->RUTA_MINICONDA_BASE}\\envs\\{$this->NOMBRE_ENTORNO}\\python.exe";

        // Comando Python: Ruta al ejecutable + Ruta al script + Argumento de texto
        $comando_python = "\"{$RUTA_PYTHON_ENV}\" \"{$this->RUTA_SCRIPT_PYTHON}\" {$arg_texto}";

        // Comando final encapsulado en CMD /C para robustez en Windows y captura de errores (2>&1)
        $comando = "cmd /C \"{$comando_python}\" 2>&1";

        try {
            $output = shell_exec($comando);
        } catch (Exception $e) {
            Log::error("Fallo critico al ejecutar shell_exec: " . $e->getMessage() . " Comando: {$comando}");
            return null;
        }

        $resultado = json_decode($output, true);

        // El script python debe devolver un JSON con la clave 'C0_vector'
        if ($resultado && isset($resultado['C0_vector']) && is_array($resultado['C0_vector'])) {
            // El resultado es el vector R^0. Lo devolvemos como JSON string.
            return json_encode($resultado['C0_vector']);
        }

        // Si no se decodifica o la clave es incorrecta, logueamos el fallo
        Log::error("Fallo en el parsing del Vector Python. Comando: {$comando}. Salida: " . ($output ?? 'NULL'));
        return null;
    }
}
