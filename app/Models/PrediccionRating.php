<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrediccionRating extends Model
{
    use HasFactory;

    protected $table = 'prediccion_rating';
    protected $primaryKey = 'id_prediccion';

    protected $fillable = [
        'id_usuario',
        'id_destino',
        'rating_predicho',
        'confianza',
        'modelo_usado',
        'fecha_prediccion',
        'factores_contextuales',
    ];

    protected $casts = [
        'rating_predicho' => 'float',
        'confianza' => 'float',
        'fecha_prediccion' => 'datetime',
        'factores_contextuales' => 'array', // Si se almacena como JSON
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
}
