<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InteraccionUD extends Model
{
    use HasFactory;

    protected $table = 'interaccion_usuario_destino';
    protected $primaryKey = 'id_interaccion';

    protected $fillable = [
        'id_usuario',
        'id_destino',
        'rating',
        'fecha_visita',
        'duracion_visita',
        'gasto_estimado',
        'comentario',
        'sentimiento',
        'medio_transporte',
        'tipo_interaccion', // 💡 NUEVO: Clave para el GNN/Backend
    ];

    protected $casts = [
        'fecha_visita' => 'date',
        'rating' => 'float',
    ];

    // Relación Inversa: Pertenece a Usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    // Relación Inversa: Pertenece a Destino
    public function destino()
    {
        return $this->belongsTo(Destino::class, 'id_destino', 'id_destino');
    }

    // Relación: Puede tener una reseña textual asociada (1:1)
    public function reseña()
    {
        return $this->hasOne(ReseñaTexto::class, 'id_interaccion', 'id_interaccion');
    }
}
