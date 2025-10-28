<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    use HasFactory;

    protected $table = 'destinos';
    protected $primaryKey = 'id_destino';

    // Eliminamos 'public $incrementing = false;' para usar el autoincremento.
    // También eliminamos 'id_destino' del $fillable ya que la DB lo genera.

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

    // Relaciones (para referencia futura)
    public function interaccionesUsuario()
    {
        // Se asume que InteraccionUD usa 'id_destino' como FK
        return $this->hasMany(InteraccionUD::class, 'id_destino', 'id_destino');
    }
}
