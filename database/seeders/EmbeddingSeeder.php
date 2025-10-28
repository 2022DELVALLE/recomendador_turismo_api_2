<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmbeddingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ejemplo de un vector de 5 dimensiones serializado como JSON
        $vector_usuario = json_encode([0.1, 0.5, -0.2, 0.8, -0.1]);
        $vector_destino = json_encode([-0.3, 0.9, 0.1, -0.5, 0.7]);
        
        DB::table('embeddings')->insert([
            [
                'tipo_nodo' => 'U',
                'id_referencia' => 1, // ID 1 del Usuario
                'vector_embedding' => $vector_usuario,
                'fecha_generacion' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tipo_nodo' => 'P',
                'id_referencia' => 1, // ID 1 del Destino
                'vector_embedding' => $vector_destino,
                'fecha_generacion' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}