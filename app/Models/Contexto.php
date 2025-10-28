<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contexto extends Model
{
    use HasFactory;

    protected $table = 'contextos';
    protected $primaryKey = 'id_contexto';

    protected $fillable = [
        'clima_actual',
        'temperatura_promedio',
        'temporada',
        'transporte',
        'densidad_turistica',
        'estado_vias',
        'nivel_seguridad',
        'recomendacion_social',
        'servicios_disponibles',
    ];

    protected $casts = [
        'servicios_disponibles' => 'boolean',
        'temperatura_promedio' => 'float',
    ];

    // Relaciones (para referencia futura)
    // Relación U-C
    public function interaccionesUsuario()
    {
        // Se asume que InteraccionUC usa 'id_contexto' como FK
        return $this->hasMany(InteraccionUC::class, 'id_contexto', 'id_contexto');
    }

    // Relación C-P
    public function relacionesDestino()
    {
        return $this->hasMany(RelacionCD::class, 'id_contexto', 'id_contexto');
    }
}
