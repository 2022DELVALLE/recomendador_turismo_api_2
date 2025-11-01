<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    use HasFactory;

    protected $table = 'destinos';
    protected $primaryKey = 'id_destino';

    // No se incluye 'id_destino' en $fillable ya que la DB lo genera.
    protected $fillable = [
        'nombre_destino',
        'categoria',
        'subcategoria',
        'latitud',
        'longitud',
        'altitud',
        'dificultad_acceso',
        'afluencia_promedio',
        'costo_promedio',
        'tiempo_visita_promedio',
        'etiquetas_tematicas',
        'temporada_alta',
    ];

    protected $casts = [
        'latitud' => 'float',
        'longitud' => 'float',
        'altitud' => 'float',
    ];

    // =========================================================
    // ACCESOR CLAVE: Genera el texto para la vectorización (Vector P^0)
    // =========================================================

    /**
     * Genera un string combinado con la información más importante del destino
     * para que el modelo S-BERT lo vectorice (Vector P^0).
     *
     * @return string
     */
    public function getTextoParaVectorizacionAttribute(): string
    {
        // Combinamos los campos que describen la experiencia y la naturaleza del destino
        $texto = "Destino turístico: {$this->nombre_destino}. ";
        $texto .= "Categoría principal: {$this->categoria}. Subcategoría: {$this->subcategoria}. ";
        $texto .= "Etiquetas temáticas: {$this->etiquetas_tematicas}. ";
        $texto .= "Dificultad de acceso: {$this->dificultad_acceso}. ";
        $texto .= "Costo promedio de visita: {$this->costo_promedio}. ";
        $texto .= "Mejor visitarlo en temporada: {$this->temporada_alta}. ";
        $texto .= "Este lugar es ideal para quienes buscan actividades de tipo {$this->categoria} o {$this->subcategoria}.";

        return $texto;
    }

    // Relaciones (para referencia futura)
    public function interaccionesUsuario()
    {
        // Se asume que InteraccionUD usa 'id_destino' como FK
        // Asegúrate de importar el modelo InteraccionUD si está en otro namespace
        return $this->hasMany(InteraccionUD::class, 'id_destino', 'id_destino');
    }
}
