<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RelacionDDSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('relacion_destino_destino')->insert([
            [
                'id_destino_origen' => 1, // Machu Picchu
                'id_destino_relacionado' => 2, // Cataratas del Iguazú
                'tipo_relacion' => 'Alto Rating Común',
                'peso_relacion' => 0.85,
                'descripcion' => 'Ambos destinos son frecuentemente calificados con 5 estrellas por el mismo segmento de usuarios.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_destino_origen' => 2,
                'id_destino_relacionado' => 1,
                'tipo_relacion' => 'Alto Rating Común',
                'peso_relacion' => 0.85,
                'descripcion' => 'Relación simétrica (Aunque en el GNN podría ser unidireccional, se almacena simétricamente en la DB).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}