<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InteraccionUE extends Model
{
    use HasFactory;

    protected $table = 'interaccion_usuario_evento';
    protected $primaryKey = 'id_ue';

    protected $fillable = [
        'id_usuario',
        'id_evento',
        'asistencia',
        'fecha_participacion',
        'gasto_evento',
        'valoracion_evento',
        'comentario_evento',
        'sentimiento_evento',
    ];

    protected $casts = [
        'asistencia' => 'boolean',
        'fecha_participacion' => 'date',
    ];

    // Relación Inversa: Pertenece a Usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    // Relación Inversa: Pertenece a Evento
    public function evento()
    {
        return $this->belongsTo(EventoFestividad::class, 'id_evento', 'id_evento');
    }
}
