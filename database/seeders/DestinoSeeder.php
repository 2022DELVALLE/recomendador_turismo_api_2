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
        DB::table('destinos')->insert(
            [
                // --- 1. Atractivos Centrales (Distrito de Tarma) ---
                [
                    'nombre_destino' => 'Plaza de Armas de Tarma',
                    'categoria' => 'Cultural',
                    'subcategoria' => 'Cívico/Histórico',
                    'latitud' => -11.4172,
                    'longitud' => -75.6908,
                    'altitud' => 3050.0,
                    'dificultad_acceso' => 'Fácil',
                    'afluencia_promedio' => 500,
                    'costo_promedio' => 0.0, // Gratuito
                    'tiempo_visita_promedio' => 0.5, // Horas
                    'etiquetas_tematicas' => 'Urbano, Fundacional, Arquitectura, Gratis, Flores, Semana Santa',
                    'temporada_alta' => 'Semana Santa, Aniversario de Tarma (Julio)',
                ],
                [
                    'nombre_destino' => 'Catedral de Santa Ana de Tarma',
                    'categoria' => 'Cultural',
                    'subcategoria' => 'Religioso/Arquitectónico',
                    'latitud' => -11.4170,
                    'longitud' => -75.6905,
                    'altitud' => 3050.0,
                    'dificultad_acceso' => 'Fácil',
                    'afluencia_promedio' => 350,
                    'costo_promedio' => 0.0,
                    'tiempo_visita_promedio' => 0.75,
                    'etiquetas_tematicas' => 'Neoclásico, Manuel A. Odría, Religión, Semana Santa, Vitrales',
                    'temporada_alta' => 'Semana Santa, Navidad',
                ],
                [
                    'nombre_destino' => 'Museo Regional Manuel A. Odría',
                    'categoria' => 'Cultural',
                    'subcategoria' => 'Histórico/Educativo',
                    'latitud' => -11.4168,
                    'longitud' => -75.6885,
                    'altitud' => 3050.0,
                    'dificultad_acceso' => 'Fácil',
                    'afluencia_promedio' => 50,
                    'costo_promedio' => 5.0, // Soles
                    'tiempo_visita_promedio' => 1.5,
                    'etiquetas_tematicas' => 'Historia, Presidentes, Arqueología, Colección, Militar',
                    'temporada_alta' => 'Fines de Semana Largos',
                ],

                // --- 2. Atractivos Arqueológicos e Históricos ---
                [
                    'nombre_destino' => 'Sitio Arqueológico de Tarmatambo',
                    'categoria' => 'Arqueológico',
                    'subcategoria' => 'Inca/Centro Administrativo',
                    'latitud' => -11.4721,
                    'longitud' => -75.6795,
                    'altitud' => 3400.0,
                    'dificultad_acceso' => 'Medio',
                    'afluencia_promedio' => 80,
                    'costo_promedio' => 5.0,
                    'tiempo_visita_promedio' => 2.0,
                    'etiquetas_tematicas' => 'Qhapaq Ñan, Colcas, Ruinas, Panorámico, Valle de Tarma',
                    'temporada_alta' => 'Mayo a Septiembre (Estación Seca)',
                ],
                [
                    'nombre_destino' => 'Ruinas Arqueológicas de Paca',
                    'categoria' => 'Arqueológico',
                    'subcategoria' => 'Pre-Inca/Centro Ceremonial',
                    'latitud' => -11.4589,
                    'longitud' => -75.7601,
                    'altitud' => 3650.0,
                    'dificultad_acceso' => 'Medio',
                    'afluencia_promedio' => 20,
                    'costo_promedio' => 0.0,
                    'tiempo_visita_promedio' => 2.5,
                    'etiquetas_tematicas' => 'Wanka, Chullpas, Restos, Vistas, Trekking',
                    'temporada_alta' => 'Mayo a Julio',
                ],
                [
                    'nombre_destino' => 'Capilla del Señor de la Cárcel',
                    'categoria' => 'Cultural',
                    'subcategoria' => 'Religioso/Mito',
                    'latitud' => -11.4185,
                    'longitud' => -75.6915,
                    'altitud' => 3050.0,
                    'dificultad_acceso' => 'Fácil',
                    'afluencia_promedio' => 100,
                    'costo_promedio' => 0.0,
                    'tiempo_visita_promedio' => 0.5,
                    'etiquetas_tematicas' => 'Leyenda, Devoción, Colonial, Centro de Tarma',
                    'temporada_alta' => 'Octubre (Señor de los Milagros)',
                ],

                // --- 3. Atractivos Naturales y de Aventura ---
                [
                    'nombre_destino' => 'Gruta de Huagapo (Distrito de Palcamayo)',
                    'categoria' => 'Natural',
                    'subcategoria' => 'Caverna/Espeleología',
                    'latitud' => -11.2915,
                    'longitud' => -75.7891,
                    'altitud' => 3572.0,
                    'dificultad_acceso' => 'Medio', // Fácil ingreso, dificultad para recorrido largo
                    'afluencia_promedio' => 300,
                    'costo_promedio' => 6.0,
                    'tiempo_visita_promedio' => 2.0,
                    'etiquetas_tematicas' => 'La Gruta que Llora, Profunda, Estalactitas, Palcamayo, Aventura',
                    'temporada_alta' => 'Enero (Aniversario), Fiestas Patrias',
                ],
                [
                    'nombre_destino' => 'Campiña de Sacsamarca (Valle de las Flores)',
                    'categoria' => 'Natural',
                    'subcategoria' => 'Paisaje/Agro-turismo',
                    'latitud' => -11.4105,
                    'longitud' => -75.7050,
                    'altitud' => 3000.0,
                    'dificultad_acceso' => 'Fácil',
                    'afluencia_promedio' => 150,
                    'costo_promedio' => 0.0, // Visita a campos, viveros pueden cobrar
                    'tiempo_visita_promedio' => 1.0,
                    'etiquetas_tematicas' => 'Flores, Claveles, Viveros, Naturaleza, Fotografía, Agro',
                    'temporada_alta' => 'Octubre-Noviembre (Día de los Muertos, mayor floración)',
                ],
                [
                    'nombre_destino' => 'Cachipuquio (Manantiales Salinos - San Pedro de Cajas)',
                    'categoria' => 'Natural',
                    'subcategoria' => 'Manantial/Geológico',
                    'latitud' => -11.2305,
                    'longitud' => -75.8902,
                    'altitud' => 4050.0,
                    'dificultad_acceso' => 'Medio',
                    'afluencia_promedio' => 40,
                    'costo_promedio' => 3.0,
                    'tiempo_visita_promedio' => 1.0,
                    'etiquetas_tematicas' => 'Agua Salada, Pozas, Tradición, San Pedro de Cajas, Naturaleza',
                    'temporada_alta' => 'Mayo a Septiembre',
                ],
                [
                    'nombre_destino' => 'Catarata Pacchacoto (Distrito de San Pedro de Cajas)',
                    'categoria' => 'Natural',
                    'subcategoria' => 'Cascada',
                    'latitud' => -11.2510,
                    'longitud' => -75.8850,
                    'altitud' => 3900.0,
                    'dificultad_acceso' => 'Medio',
                    'afluencia_promedio' => 30,
                    'costo_promedio' => 0.0,
                    'tiempo_visita_promedio' => 1.5,
                    'etiquetas_tematicas' => 'Agua, Paisaje, Aventura, Río, Fotografía',
                    'temporada_alta' => 'Meses de Lluvias (mayor caudal)',
                ],
                [
                    'nombre_destino' => 'Laguna de Cocón (Distrito de Palcamayo)',
                    'categoria' => 'Natural',
                    'subcategoria' => 'Laguna Altoandina',
                    'latitud' => -11.3320,
                    'longitud' => -75.8450,
                    'altitud' => 3800.0,
                    'dificultad_acceso' => 'Medio/Alto',
                    'afluencia_promedio' => 20,
                    'costo_promedio' => 0.0,
                    'tiempo_visita_promedio' => 3.0,
                    'etiquetas_tematicas' => 'Ecoturismo, Pesca, Cordillera, Paisaje, Aves',
                    'temporada_alta' => 'Mayo a Agosto',
                ],

                // --- 4. Atractivos Religiosos y Productivos ---
                [
                    'nombre_destino' => 'Santuario del Señor de Muruhuay (Distrito de Acobamba)',
                    'categoria' => 'Cultural',
                    'subcategoria' => 'Religioso/Santuario Rupestre',
                    'latitud' => -11.3789,
                    'longitud' => -75.6568,
                    'altitud' => 2940.0,
                    'dificultad_acceso' => 'Fácil',
                    'afluencia_promedio' => 1000,
                    'costo_promedio' => 0.0,
                    'tiempo_visita_promedio' => 1.0,
                    'etiquetas_tematicas' => 'Milagroso, Peregrinación, Mayo, Acobamba, Devoción, Rupestre',
                    'temporada_alta' => 'Fiesta Central (Mayo)',
                ],
                [
                    'nombre_destino' => 'Distrito de San Pedro de Cajas (Cuna del Tapiz)',
                    'categoria' => 'Cultural',
                    'subcategoria' => 'Artesanal/Textil',
                    'latitud' => -11.2471,
                    'longitud' => -75.8955,
                    'altitud' => 4014.0,
                    'dificultad_acceso' => 'Fácil',
                    'afluencia_promedio' => 90,
                    'costo_promedio' => 0.0,
                    'tiempo_visita_promedio' => 2.0,
                    'etiquetas_tematicas' => 'Tapices 3D, Artesanía, Tejidos, Alpaca, Altoandino, Folclore',
                    'temporada_alta' => 'Carnavales, Fiestas Patronales (Junio)',
                ],
                [
                    'nombre_destino' => 'Hacienda de Sacsamarca / Ex-Hacienda La Florida',
                    'categoria' => 'Cultural',
                    'subcategoria' => 'Histórico/Agro-turismo',
                    'latitud' => -11.4280,
                    'longitud' => -75.7250,
                    'altitud' => 3000.0,
                    'dificultad_acceso' => 'Fácil',
                    'afluencia_promedio' => 70,
                    'costo_promedio' => 10.0, // Costo de tour o consumo
                    'tiempo_visita_promedio' => 2.0,
                    'etiquetas_tematicas' => 'Casona, Colonial, Flores, Productos Lácteos, Eventos',
                    'temporada_alta' => 'Fin de Semana, Temporada de Flores',
                ],
            ]
        );
    }
}
