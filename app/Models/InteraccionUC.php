<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InteraccionUC extends Model
{
    use HasFactory;

    protected $table = 'interaccion_usuario_contexto';
    protected $primaryKey = 'id_uc';

    protected $fillable = [
        'id_usuario',
        'id_contexto',
        'clima_visita',
        'transporte_usado',
        'seguridad_percibida',
        'servicios_utilizados',
    ];

    protected $casts = [
        'servicios_utilizados' => 'boolean',
    ];

    // Relación Inversa: Pertenece a Usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    // Relación Inversa: Pertenece a Contexto
    public function contexto()
    {
        return $this->belongsTo(Contexto::class, 'id_contexto', 'id_contexto');
    }
}
