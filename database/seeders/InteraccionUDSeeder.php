<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InteraccionUDSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('interaccion_usuario_destino')->insert([
            [
                'id_usuario' => 1, // Usuario 1 (Elena Ríos)
                'id_destino' => 1, // Destino 1 (Machu Picchu)
                'rating' => 5.0,
                'fecha_visita' => '2024-03-15',
                'duracion_visita' => 4.0,
                'gasto_estimado' => 120.0,
                'comentario' => 'Experiencia inolvidable, la organización fue excelente.',
                'sentimiento' => 0.9,
                'medio_transporte' => 'Tren',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_usuario' => 2, // Usuario 2 (Javier Solís)
                'id_destino' => 1, // Destino 1 (Machu Picchu)
                'rating' => 4.2,
                'fecha_visita' => '2024-04-20',
                'duracion_visita' => 3.5,
                'gasto_estimado' => 150.0,
                'comentario' => 'Impresionante, aunque había mucha gente en la entrada.',
                'sentimiento' => 0.5,
                'medio_transporte' => 'Bus',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}