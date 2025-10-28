<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContextoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('contextos')->insert([
            // --- Contexto 1: Época Seca y Templada (Temporada Alta) ---
            [
                'clima_actual' => 'Templado y Soleado',
                'temperatura_promedio' => 15.0, // Rango de 10°C a 20°C
                'temporada' => 'Seca (Abril - Octubre)',
                'transporte' => 'Bus, Colectivo y Taxi',
                'densidad_turistica' => 0.75, // Media-Alta en meses centrales (Jun-Ago)
                'estado_vias' => 'Muy Bueno', // Ideal para viajar
                'nivel_seguridad' => 9, // La sierra central es generalmente segura
                'recomendacion_social' => 4.8, // Muy recomendado por el clima
                'servicios_disponibles' => true, // Todos los servicios turísticos operativos
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // --- Contexto 2: Época de Lluvias y Fría (Temporada Baja) ---
            [
                'clima_actual' => 'Lluvioso con Niebla',
                'temperatura_promedio' => 11.0, // Rango de 8°C a 16°C
                'temporada' => 'Lluviosa (Noviembre - Marzo)',
                'transporte' => 'Bus y Taxi (puede haber demoras)',
                'densidad_turistica' => 0.35, // Baja
                'estado_vias' => 'Regular', // Posibilidad de deslizamientos o neblina
                'nivel_seguridad' => 7,
                'recomendacion_social' => 3.5,
                'servicios_disponibles' => true, // Servicios básicos disponibles
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}