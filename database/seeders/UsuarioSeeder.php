<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsuarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('usuarios')->insert([
            [
                'nombre' => 'Elena Ríos',
                'edad' => 32,
                'genero' => 'Femenino',
                'origen_geografico' => 'España',
                'tipo_turista' => 'Aventura',
                'nivel_gasto' => 150.50,
                'preferencias_texto' => 'Me encanta el senderismo y la comida local.',
                'dispositivo_acceso' => 'Móvil',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Javier Solís',
                'edad' => 45,
                'genero' => 'Masculino',
                'origen_geografico' => 'México',
                'tipo_turista' => 'Cultural',
                'nivel_gasto' => 200.00,
                'preferencias_texto' => 'Busco museos y sitios históricos poco concurridos.',
                'dispositivo_acceso' => 'Desktop',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
