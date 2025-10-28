<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RelacionDESeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('relacion_destino_evento')->insert([
            [
                'id_destino' => 1, // Ejemplo: Machu Picchu
                'id_evento' => 1, // Ejemplo: Festival de las Flores
                'tipo_vinculo' => 'Evento Primario',
                'impacto_turistico' => 0.95,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_destino' => 2, // Ejemplo: Cataratas del Iguazú
                'id_evento' => 2, // Ejemplo: Concierto de Rock
                'tipo_vinculo' => 'Acceso Cercano',
                'impacto_turistico' => 0.6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}