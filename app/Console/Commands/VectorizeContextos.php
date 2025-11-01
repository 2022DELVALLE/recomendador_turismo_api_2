<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contexto;
use App\Models\Embedding;
use Exception;
use Illuminate\Support\Facades\Log;

class VectorizeContextos extends Command
{
    /**
     * El nombre y la firma del comando de consola.
     * php artisan vectorize:contextos
     * @var string
     */
    protected $signature = 'vectorize:contextos';

    /**
     * La descripción del comando de consola.
     * @var string
     */
    protected $description = 'Genera y guarda los vectores de embedding (C^0) para todos los contextos existentes.';

    /**
     * Ruta al script de Python para vectorización de contextos.
     * ¡AJUSTA ESTA RUTA SI ES NECESARIO!
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
        $this->info("Iniciando la vectorización de contextos (C^0)...");
        $contextos = Contexto::all();
        $totalContextos = $contextos->count();
        $this->info("Total de contextos a procesar: {$totalContextos}");

        if ($totalContextos === 0) {
            $this->warn("No se encontraron contextos en la base de datos.");
            return 0;
        }

        $bar = $this->output->createProgressBar($totalContextos);
        $bar->start();

        foreach ($contextos as $contexto) {
            $id = $contexto->id_contexto;
            
            // 🛑 CORRECCIÓN CLAVE: Usamos el nuevo Accessor definido en el modelo Contexto
            $texto_contexto = $contexto->texto_para_vectorizacion;
            
            // La verificación de nulidad sigue siendo útil por si el Accessor devuelve algo vacío.
            if (empty($texto_contexto)) {
                $this->warn("\n[SKIP] Contexto ID {$id} saltado: El texto combinado (Accessor) está vacío. Revise los datos de contexto en la BD.");
                $bar->advance();
                continue; // Pasa al siguiente contexto
            }

            try {
                // 1. Ejecutar script Python
                $vector_json = $this->ejecutarScriptPython($texto_contexto);

                if ($vector_json) {
                    // 2. Guardar el Vector C^0 (o actualizar si existe)
                    Embedding::updateOrCreate(
                        [
                            'tipo_nodo' => 'C',
                            'id_referencia' => $id,
                        ],
                        [
                            'vector_embedding' => $vector_json,
                            'fecha_generacion' => now(),
                        ]
                    );
                    $this->line("\n[OK] Contexto ID {$id} vectorizado y guardado.");
                } else {
                    $this->warn("\n[FAIL] Contexto ID {$id}: Error al obtener el vector de Python.");
                }

            } catch (Exception $e) {
                $this->error("\n[CRITICO] Fallo el procesamiento para ID {$id}: " . $e->getMessage());
                Log::error("Vectorización de Contexto Fallida: ID {$id}. Error: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n\nVectorización de Contextos Finalizada.");

        return 0;
    }

    /**
     * Llama al script de Python para generar el vector C^0.
     * Esta función usa la misma lógica de comando robusto que el EmbeddingProcessor.
     */
    private function ejecutarScriptPython(string $texto_contexto): ?string
    {
        $arg_texto = escapeshellarg($texto_contexto);

        $RUTA_PYTHON_ENV = "{$this->RUTA_MINICONDA_BASE}\\envs\\{$this->NOMBRE_ENTORNO}\\python.exe";
        
        // Comando Python con comillas y doble barra (para evitar el error \e)
        $comando_python = "\"{$RUTA_PYTHON_ENV}\" \"{$this->RUTA_SCRIPT_PYTHON}\" {$arg_texto}";

        // Encapsulamos en CMD /C para robustez en Windows
        $comando = "cmd /C \"{$comando_python}\" 2>&1"; 

        $output = shell_exec($comando);
        
        // Intenta decodificar el output (debe ser el JSON del vector)
        $resultado = json_decode($output, true);

        if ($resultado && isset($resultado['C0_vector'])) {
            // El script Python solo devuelve el vector C0
            return json_encode($resultado['C0_vector']);
        }
        
        Log::error("Fallo de Vectorización de Contexto Python. Comando: {$comando}. Salida: " . $output);
        return null;
    }
}
