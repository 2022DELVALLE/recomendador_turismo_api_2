<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class PrediccionRatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('prediccion_rating')->insert([
            [
                'id_usuario' => 1, 
                'id_destino' => 2, // Predicción para Usuario 1 y Destino 2 (Cataratas)
                'rating_predicho' => 4.95,
                'confianza' => 0.92,
                'modelo_usado' => 'GCN-Contextual',
                'fecha_prediccion' => now(),
                'factores_contextuales' => json_encode(['temporada' => 'alta', 'clima' => 'soleado']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_usuario' => 2, 
                'id_destino' => 1, // Predicción para Usuario 2 y Destino 1 (Machu Picchu)
                'rating_predicho' => 3.80,
                'confianza' => 0.75,
                'modelo_usado' => 'GraphSAGE',
                'fecha_prediccion' => now(),
                'factores_contextuales' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
