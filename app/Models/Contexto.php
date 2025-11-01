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

    // =========================================================
    // NUEVO: Descriptor para Vectorización (Paso Clave)
    // =========================================================

    /**
     * Genera un string combinado con toda la información relevante para el embedding.
     * Esta es la "descripción del contexto" que el modelo S-BERT usará.
     *
     * @return string
     */
    public function getTextoParaVectorizacionAttribute(): string
    {
        $texto = "Contexto actual de Tarma: ";
        $texto .= "Clima {$this->clima_actual}, ";
        $texto .= "Temporada de {$this->temporada}, ";
        $texto .= "Temperatura promedio de {$this->temperatura_promedio} grados. ";
        $texto .= "Transporte {$this->transporte}. ";
        $texto .= "Densidad turística {$this->densidad_turistica}. ";
        $texto .= "Recomendación social: {$this->recomendacion_social}. ";

        if ($this->servicios_disponibles) {
            $texto .= "Los servicios y vías están en buen estado. ";
        } else {
            $texto .= "Los servicios están limitados y el estado de vías es {$this->estado_vias}. ";
        }

        $texto .= "Nivel de seguridad: {$this->nivel_seguridad}.";

        return $texto;
    }

    // Relaciones (para referencia futura)
    // Relación U-C
    public function interaccionesUsuario()
    {
        return $this->hasMany(InteraccionUC::class, 'id_contexto', 'id_contexto');
    }

    // Relación C-P
    public function relacionesDestino()
    {
        return $this->hasMany(RelacionCD::class, 'id_contexto', 'id_contexto');
    }
}
