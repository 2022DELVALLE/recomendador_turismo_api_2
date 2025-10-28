<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    // ELIMINAMOS 'public $incrementing = false;'

    protected $fillable = [
        // ELIMINAMOS 'id_usuario' de fillable
        'nombre',
        'edad',
        'genero',
        'origen_geografico',
        'tipo_turista',
        'nivel_gasto',
        'preferencias_texto',
        'dispositivo_acceso',
    ];
    // Opcional: Define las relaciones que tendrá (para futuras interacciones)
    public function interaccionesDestino()
    {
        return $this->hasMany(InteraccionUD::class, 'id_usuario', 'id_usuario');
    }
}
