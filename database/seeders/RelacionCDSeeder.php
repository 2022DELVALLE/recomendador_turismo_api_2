<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RelacionCDSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('relacion_contexto_destino')->insert([
            [
                'id_contexto' => 1, // Contexto: Soleado/Verano
                'id_destino' => 1, // Destino: Machu Picchu (cultural/montaña)
                'impacto_clima' => 'Óptimo',
                'peso_contexto' => 0.9,
                'es_accesible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_contexto' => 2, // Contexto: Lluvioso/Invierno
                'id_destino' => 2, // Destino: Cataratas del Iguazú (cascada)
                'impacto_clima' => 'Negativo',
                'peso_contexto' => 0.3,
                'es_accesible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
