<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EventoFestividad; // Importamos el modelo
use App\Models\Embedding;
use Exception;
use Illuminate\Support\Facades\Log;

class VectorizeEventos extends Command
{
    /**
     * El nombre y la firma del comando de consola.
     * php artisan vectorize:eventos
     * @var string
     */
    protected $signature = 'vectorize:eventos';

    /**
     * La descripción del comando de consola.
     * @var string
     */
    protected $description = 'Genera y guarda los vectores de embedding (E^0) para todos los eventos y festividades.';

    /**
     * Ruta al script de Python para vectorización.
     * ¡AJUSTA ESTAS RUTAS SI ES NECESARIO! (Usamos el mismo script de vectorización)
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
        $this->info("Iniciando la vectorización de Eventos y Festividades (E^0)...");
        $eventos = EventoFestividad::all();
        $totalEventos = $eventos->count();
        $this->info("Total de eventos a procesar: {$totalEventos}");

        if ($totalEventos === 0) {
            $this->warn("No se encontraron eventos en la base de datos.");
            return 0;
        }

        $bar = $this->output->createProgressBar($totalEventos);
        $bar->start();

        foreach ($eventos as $evento) {
            $id = $evento->id_evento;

            // Usamos el Accessor del modelo EventoFestividad
            $texto_evento = $evento->texto_para_vectorizacion;

            if (empty($texto_evento)) {
                $this->warn("\n[SKIP] Evento ID {$id} saltado: El texto combinado (Accessor) está vacío. Revise los datos en la BD.");
                $bar->advance();
                continue;
            }

            try {
                // 1. Ejecutar script Python
                $vector_json = $this->ejecutarScriptPython($texto_evento);

                if ($vector_json) {
                    // 2. Guardar el Vector E^0 (o actualizar si existe)
                    Embedding::updateOrCreate(
                        [
                            'tipo_nodo' => 'E', // Tipo: Evento/Festividad
                            'id_referencia' => $id,
                        ],
                        [
                            'vector_embedding' => $vector_json,
                            'fecha_generacion' => now(),
                        ]
                    );
                    $this->line("\n[OK] Evento ID {$id} vectorizado y guardado.");
                } else {
                    $this->warn("\n[FAIL] Evento ID {$id}: Error al obtener el vector de Python.");
                }
            } catch (Exception $e) {
                $this->error("\n[CRITICO] Fallo el procesamiento para ID {$id}: " . $e->getMessage());
                Log::error("Vectorización de Evento Fallida: ID {$id}. Error: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n\nVectorización de Eventos y Festividades Finalizada.");

        return 0;
    }

    /**
     * Llama al script de Python para generar el vector E^0.
     * Usamos la misma lógica que en los otros comandos de vectorización.
     */
    private function ejecutarScriptPython(string $texto): ?string
    {
        $arg_texto = escapeshellarg($texto);

        $RUTA_PYTHON_ENV = "{$this->RUTA_MINICONDA_BASE}\\envs\\{$this->NOMBRE_ENTORNO}\\python.exe";

        $comando_python = "\"{$RUTA_PYTHON_ENV}\" \"{$this->RUTA_SCRIPT_PYTHON}\" {$arg_texto}";
        $comando = "cmd /C \"{$comando_python}\" 2>&1";

        $output = shell_exec($comando);

        $resultado = json_decode($output, true);

        if ($resultado && isset($resultado['C0_vector'])) {
            // El script python devuelve el vector con la clave 'C0_vector', lo usamos para E^0 también
            return json_encode($resultado['C0_vector']);
        }

        Log::error("Fallo de Vectorización de Evento Python. Comando: {$comando}. Salida: " . $output);
        return null;
    }
}
