<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class ReseñaTextoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('reseña_texto')->insert([
            [
                'id_interaccion' => 1, // Interacción U1-D1 (Machu Picchu)
                'contenido_original' => 'Experiencia inolvidable, la organización fue excelente. Los guías muy amables.',
                'puntuacion_sentimiento' => 0.9,
                'lenguaje' => 'es',
                'topicos_extraidos' => json_encode(['guías', 'organización', 'excelente']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_interaccion' => 2, // Interacción U2-D1 (Machu Picchu)
                'contenido_original' => 'Impresionante, aunque había mucha gente en la entrada. Las vistas lo compensaron.',
                'puntuacion_sentimiento' => 0.5,
                'lenguaje' => 'es',
                'topicos_extraidos' => json_encode(['vistas', 'gente', 'compensaron']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}