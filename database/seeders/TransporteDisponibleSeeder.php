<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TransporteDisponible;

class TransporteDisponibleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Datos de ejemplo para el transporte en Tarma (ajustar según la realidad)
        $transportes = [
            [
                'tipo_transporte' => 'Mototaxi',
                'costo_base_minimo' => 3.00,
                'horario_disponibilidad' => '5:00 - 23:00',
                'activo' => true,
            ],
            [
                'tipo_transporte' => 'Taxi Urbano',
                'costo_base_minimo' => 5.00,
                'horario_disponibilidad' => '24/7',
                'activo' => true,
            ],
            [
                'tipo_transporte' => 'Bus/Colectivo Urbano',
                'costo_base_minimo' => 1.50,
                'horario_disponibilidad' => '6:00 - 20:00',
                'activo' => true,
            ],
            [
                'tipo_transporte' => 'Bicicleta (Alquiler)',
                'costo_base_minimo' => 10.00, // Costo por hora o día
                'horario_disponibilidad' => '9:00 - 18:00',
                'activo' => false, // Ejemplo de un servicio que podría estar inactivo temporalmente
            ],
        ];

        foreach ($transportes as $transporte) {
            TransporteDisponible::create($transporte);
        }
    }
}
