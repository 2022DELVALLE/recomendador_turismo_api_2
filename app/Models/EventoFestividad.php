<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoFestividad extends Model
{
    use HasFactory;

    protected $table = 'evento_festividades';
    protected $primaryKey = 'id_evento';
    
    protected $fillable = [
        'nombre_evento',
        'tipo_evento',
        'fecha_inicio',
        'fecha_fin',
        'lugar_asociado',
        'afluencia_esperada',
        'costo_entrada',
        'valor_cultural',
        'palabras_clave',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    // Relación: Un evento pertenece a un destino (1:N Inversa)
    public function destino()
    {
        // El FK es 'lugar_asociado', que referencia a 'id_destino'
        return $this->belongsTo(Destino::class, 'lugar_asociado', 'id_destino');
    }
}