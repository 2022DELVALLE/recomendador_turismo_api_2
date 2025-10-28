<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InteraccionUCSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('interaccion_usuario_contexto')->insert([
            [
                'id_usuario' => 1, // Usuario 1 (Elena Ríos)
                'id_contexto' => 1, // Contexto 1 (Soleado/Verano)
                'clima_visita' => 'Soleado',
                'transporte_usado' => 'Bus local',
                'seguridad_percibida' => 9,
                'servicios_utilizados' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_usuario' => 2, // Usuario 2 (Javier Solís)
                'id_contexto' => 2, // Contexto 2 (Lluvioso/Invierno)
                'clima_visita' => 'Lluvioso',
                'transporte_usado' => 'Metro',
                'seguridad_percibida' => 5,
                'servicios_utilizados' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}