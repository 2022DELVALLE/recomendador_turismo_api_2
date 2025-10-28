<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventoFestividadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // NOTA: Usamos 5 para la Plaza de Armas, y asumimos que Muruhuay es 2 y Sacsamarca es 4
        
        DB::table('evento_festividades')->insert([
            // --- 1. Semana Santa Tarmeña: Usa ID 5 ---
            [
                'nombre_evento' => 'Semana Santa de Tarma',
                'tipo_evento' => 'Religioso/Cultural',
                'fecha_inicio' => '2025-04-13', 
                'fecha_fin' => '2025-04-20', 
                'lugar_asociado' => 5, // ¡CORREGIDO! Referencia a la Plaza de Armas (ID 5)
                'afluencia_esperada' => 22000, 
                'costo_entrada' => 0.0, 
                'valor_cultural' => 10, 
                'palabras_clave' => 'Alfombras de flores, Procesión, Catolicismo, Fe, Chonguinada',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // --- 2. Festividad del Señor de Muruhuay: ID 2 ---
            [
                'nombre_evento' => 'Festividad del Señor de Muruhuay',
                'tipo_evento' => 'Religioso/Peregrinación',
                'fecha_inicio' => '2025-05-01', 
                'fecha_fin' => '2025-05-31', 
                'lugar_asociado' => 2, // Asumiendo ID 2 para Muruhuay
                'afluencia_esperada' => 18000, 
                'costo_entrada' => 0.0,
                'valor_cultural' => 9, 
                'palabras_clave' => 'Milagro, Devoción, Santuario, Danza, Chonguinada, Mayo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // --- 3. Festival de las Flores / Quriwayta: ID 4 ---
            [
                'nombre_evento' => 'Festival Quriwayta (Festival de las Flores)',
                'tipo_evento' => 'Cultural/Floricultura',
                'fecha_inicio' => '2025-11-08',
                'fecha_fin' => '2025-11-10',
                'lugar_asociado' => 4, // Asumiendo ID 4 para Campiña de Sacsamarca
                'afluencia_esperada' => 5000, 
                'costo_entrada' => 5.0, 
                'valor_cultural' => 7,
                'palabras_clave' => 'Flores, Desfile alegórico, Primavera, Agricultura, Carros alegóricos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // --- 4. Aniversario de Tarma y Fiestas Patrias: Usa ID 5 ---
            [
                'nombre_evento' => 'Semana Tarmeña y Fiestas Patrias',
                'tipo_evento' => 'Cívico/Cultural',
                'fecha_inicio' => '2025-07-21',
                'fecha_fin' => '2025-07-29',
                'lugar_asociado' => 5, // ¡CORREGIDO! Referencia a la Plaza de Armas (ID 5)
                'afluencia_esperada' => 12000, 
                'costo_entrada' => 0.0,
                'valor_cultural' => 8,
                'palabras_clave' => 'Aniversario, Desfiles, Gastronomía, Exhibiciones, Julio',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}