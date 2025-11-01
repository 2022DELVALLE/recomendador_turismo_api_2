<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReseñaTexto;
use App\Services\VectorizacionService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class RevectorizeReseñas extends Command
{
    /**
     * El nombre y la firma del comando de consola.
     * php artisan revectorize:reseñas
     * @var string
     */
    protected $signature = 'revectorize:reseñas';

    /**
     * La descripción del comando de consola.
     * @var string
     */
    protected $description = 'Genera o actualiza los vectores de embedding (R^0) para las reseñas existentes.';

    // Inyectamos el servicio de vectorización
    protected $vectorizacionService;

    public function __construct(VectorizacionService $vectorizacionService)
    {
        parent::__construct();
        $this->vectorizacionService = $vectorizacionService;
    }

    /**
     * Ejecuta el comando de consola.
     */
    public function handle()
    {
        $this->info("Iniciando la revectorización de Reseñas (R^0)...");
        
        try {
            // Buscamos todas las reseñas que no tengan vector
            $reseñas = ReseñaTexto::whereNull('vector_reseña')
                                   ->orWhere('vector_reseña', '')
                                   ->get();
        } catch (QueryException $e) {
            // Manejo específico del error de columna inexistente
            if (str_contains($e->getMessage(), 'Unknown column') && str_contains($e->getMessage(), 'vector_reseña')) {
                $this->error("\n[ERROR CRÍTICO DE BD] La columna 'vector_reseña' NO existe en la tabla 'reseña_texto'.");
                $this->error("=> NECESITAS crear una migración y ejecutar 'php artisan migrate' para añadir la columna TEXT.");
                $this->error("=> Procesando TODAS las reseñas temporalmente (aunque ya tengan vector) para continuar...");
                // Si la columna no existe, cargamos todos los datos (lo cual puede ser lento)
                $reseñas = ReseñaTexto::all(); 
            } else {
                // Otro error de base de datos
                throw $e;
            }
        }
        
        $totalReseñas = $reseñas->count();
        $this->info("Total de reseñas a procesar: {$totalReseñas}");

        if ($totalReseñas === 0) {
            $this->warn("No se encontraron reseñas pendientes de vectorización.");
            return 0;
        }

        $bar = $this->output->createProgressBar($totalReseñas);
        $bar->start();

        foreach ($reseñas as $reseña) {
            $id = $reseña->id_reseña;
            
            // Usamos el Accessor para obtener el texto a vectorizar
            $texto_reseña = $reseña->texto_para_vectorizacion;

            if (empty($texto_reseña)) {
                $this->warn("\n[SKIP] Reseña ID {$id} saltada: El texto de la reseña está vacío.");
                $bar->advance();
                continue;
            }

            try {
                // 1. Ejecutar el script Python usando el servicio inyectado
                $vectorR0_json = $this->vectorizacionService->ejecutarVectorizacionPython($texto_reseña);

                if ($vectorR0_json) {
                    // 2. Guardar el Vector R^0
                    $reseña->vector_reseña = $vectorR0_json;
                    $reseña->save();
                    $this->line("\n[OK] Reseña ID {$id} vectorizada y guardada.");
                } else {
                    $this->warn("\n[FAIL] Reseña ID {$id}: Error al obtener el vector de Python.");
                }

            } catch (Exception $e) {
                $this->error("\n[CRITICO] Fallo el procesamiento para ID {$id}: " . $e->getMessage());
                Log::error("Revectorización de Reseña Fallida: ID {$id}. Error: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n\nRevectorización de Reseñas Finalizada.");

        return 0;
    }
}
