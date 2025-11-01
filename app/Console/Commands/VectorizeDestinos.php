<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Destino; // Importamos el modelo Destino
use App\Models\Embedding;
use Exception;
use Illuminate\Support\Facades\Log;

class VectorizeDestinos extends Command
{
    /**
     * El nombre y la firma del comando de consola.
     * php artisan vectorize:destinos
     * @var string
     */
    protected $signature = 'vectorize:destinos';

    /**
     * La descripción del comando de consola.
     * @var string
     */
    protected $description = 'Genera y guarda los vectores de embedding (P^0) para todos los destinos turísticos.';

    /**
     * Ruta al script de Python para vectorización.
     * ¡AJUSTA ESTAS RUTAS SI ES NECESARIO!
     * @var string
     */
    private $RUTA_SCRIPT_PYTHON = 'C:\laragon\www\prueba_devue\recomendador_turismo_api\scripts\vectorizar_contexto.py';
    private $RUTA_MINICONDA_BASE = 'C:\Users\KENYO\miniconda3';
    private $NOMBRE_ENTORNO = 'tarma_ai';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle()
    {
        $this->info("Iniciando la vectorización de Destinos (P^0)...");
        $destinos = Destino::all();
        $totalDestinos = $destinos->count();
        $this->info("Total de destinos a procesar: {$totalDestinos}");

        if ($totalDestinos === 0) {
            $this->warn("No se encontraron destinos en la base de datos.");
            return 0;
        }

        $bar = $this->output->createProgressBar($totalDestinos);
        $bar->start();

        foreach ($destinos as $destino) {
            $id = $destino->id_destino;

            // Usamos el Accessor del modelo Destino definido en el Canvas
            $texto_destino = $destino->texto_para_vectorizacion;

            if (empty($texto_destino)) {
                $this->warn("\n[SKIP] Destino ID {$id} saltado: El texto combinado (Accessor) está vacío. Revise los datos en la BD.");
                $bar->advance();
                continue;
            }

            try {
                // 1. Ejecutar script Python
                $vector_json = $this->ejecutarScriptPython($texto_destino);

                if ($vector_json) {
                    // 2. Guardar el Vector P^0 (o actualizar si existe)
                    Embedding::updateOrCreate(
                        [
                            'tipo_nodo' => 'P', // Tipo: Punto de Interés (Destino)
                            'id_referencia' => $id,
                        ],
                        [
                            'vector_embedding' => $vector_json,
                            'fecha_generacion' => now(),
                        ]
                    );
                    $this->line("\n[OK] Destino ID {$id} vectorizado y guardado.");
                } else {
                    $this->warn("\n[FAIL] Destino ID {$id}: Error al obtener el vector de Python.");
                }
            } catch (Exception $e) {
                $this->error("\n[CRITICO] Fallo el procesamiento para ID {$id}: " . $e->getMessage());
                Log::error("Vectorización de Destino Fallida: ID {$id}. Error: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n\nVectorización de Destinos Finalizada.");

        return 0;
    }

    /**
     * Llama al script de Python para generar el vector P^0.
     * Usamos la misma lógica que en los otros comandos de vectorización.
     */
    private function ejecutarScriptPython(string $texto): ?string
    {
        // Se asume que el script de python recibe el texto como primer argumento
        $arg_texto = escapeshellarg($texto);

        $RUTA_PYTHON_ENV = "{$this->RUTA_MINICONDA_BASE}\\envs\\{$this->NOMBRE_ENTORNO}\\python.exe";

        // Comando robusto para la ejecución del script
        $comando_python = "\"{$RUTA_PYTHON_ENV}\" \"{$this->RUTA_SCRIPT_PYTHON}\" {$arg_texto}";
        $comando = "cmd /C \"{$comando_python}\" 2>&1";

        $output = shell_exec($comando);

        $resultado = json_decode($output, true);

        if ($resultado && isset($resultado['C0_vector'])) {
            // El script python devuelve el vector con la clave 'C0_vector', lo usamos para P^0 también
            // Solo devolvemos el vector JSON.
            return json_encode($resultado['C0_vector']);
        }

        Log::error("Fallo de Vectorización de Destino Python. Comando: {$comando}. Salida: " . $output);
        return null;
    }
}
