<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DestinoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('destinos')->insert([
            // ID 1
            [
                'nombre_destino' => 'Gruta de Huagapo',
                'categoria' => 'Naturaleza',
                'subcategoria' => 'Cueva/Gruta',
                'latitud' => -11.4589, // Aproximado, Palcamayo, Tarma
                'longitud' => -75.7600, // Aproximado
                'altitud' => 3572.0, // Altitud de Palcamayo
                'dificultad_acceso' => 'Media',
                'afluencia_promedio' => 800, // Estimado
                'costo_promedio' => 5.0, // Tarifa de entrada (Moneda local - PEN)
                'tiempo_visita_promedio' => 2.0, // Horas
                'etiquetas_tematicas' => 'Espeleología, Misticismo, Geología, Profunda',
                'temporada_alta' => 'Abril-Octubre',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID 2
            [
                'nombre_destino' => 'Santuario del Señor de Muruhuay',
                'categoria' => 'Cultural',
                'subcategoria' => 'Religioso',
                'latitud' => -11.4111, // Aproximado, Acobamba
                'longitud' => -75.6989, // Aproximado
                'altitud' => 2940.0, // Aproximado de Acobamba
                'dificultad_acceso' => 'Baja',
                'afluencia_promedio' => 1500, // Estimado, aumenta en festividad
                'costo_promedio' => 0.0, // Entrada libre
                'tiempo_visita_promedio' => 1.5,
                'etiquetas_tematicas' => 'Fe, Peregrinación, Festividad, Valle',
                'temporada_alta' => 'Mayo-Junio (Fiesta)', // Asumiendo que acortaste por el error anterior
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID 3
            [
                'nombre_destino' => 'Complejo Arqueológico de Tarmatambo',
                'categoria' => 'Cultural',
                'subcategoria' => 'Arqueología',
                'latitud' => -11.4180, // Aproximado
                'longitud' => -75.7081, // Aproximado
                'altitud' => 3450.0, // Aproximado
                'dificultad_acceso' => 'Media',
                'afluencia_promedio' => 250, // Estimado
                'costo_promedio' => 5.0, // Tarifa de entrada (Moneda local - PEN)
                'tiempo_visita_promedio' => 2.5,
                'etiquetas_tematicas' => 'Inca, Histórico, Qhapaq Ñan, Administrativo',
                'temporada_alta' => 'Junio-Agosto',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // ID 4
            [
                'nombre_destino' => 'Campiña de Sacsamarca (Valle de las Flores)',
                'categoria' => 'Naturaleza',
                'subcategoria' => 'Paisaje Cultural',
                'latitud' => -11.4245, // Aproximado de la zona
                'longitud' => -75.7198, // Aproximado de la zona
                'altitud' => 3050.0, // Similar a la ciudad de Tarma
                'dificultad_acceso' => 'Baja',
                'afluencia_promedio' => 700, // Estimado
                'costo_promedio' => 0.0, // Libre o costo mínimo por huerto
                'tiempo_visita_promedio' => 3.0,
                'etiquetas_tematicas' => 'Flores, Agricultura, Paisaje, Fotografía',
                'temporada_alta' => 'Septiembre-Noviembre', // Asumiendo que acortaste por el error anterior
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // --- NUEVO REGISTRO: ID 5 (Para eventos centrales) ---
            [
                'nombre_destino' => 'Plaza de Armas y Centro Histórico de Tarma',
                'categoria' => 'Cultural',
                'subcategoria' => 'Arquitectura/Urbano',
                'latitud' => -11.4111, // Coordenada de la Plaza de Armas
                'longitud' => -75.6944,
                'altitud' => 3050.0,
                'dificultad_acceso' => 'Baja',
                'afluencia_promedio' => 3000,
                'costo_promedio' => 0.0,
                'tiempo_visita_promedio' => 1.0,
                'etiquetas_tematicas' => 'Arquitectura, Historia, Religioso, Desfiles',
                'temporada_alta' => 'Semana Santa, Fiestas Patrias',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}