<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InteraccionUESeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('interaccion_usuario_evento')->insert([
            [
                'id_usuario' => 1, // Usuario 1 (Elena Ríos)
                'id_evento' => 1, // Evento 1 (Festival de las Flores)
                'asistencia' => true,
                'fecha_participacion' => '2025-08-05',
                'gasto_evento' => 50.0,
                'valoracion_evento' => 4.8,
                'comentario_evento' => 'Muy colorido y organizado, valió la pena el viaje.',
                'sentimiento_evento' => 0.85,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_usuario' => 2, // Usuario 2 (Javier Solís)
                'id_evento' => 2, // Evento 2 (Concierto de Rock)
                'asistencia' => true,
                'fecha_participacion' => '2025-05-20',
                'gasto_evento' => 100.0,
                'valoracion_evento' => 3.5,
                'comentario_evento' => 'Buena música, pero la logística de acceso fue caótica.',
                'sentimiento_evento' => 0.1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
