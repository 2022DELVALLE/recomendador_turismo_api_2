<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReseñaTexto extends Model
{
    use HasFactory;

    protected $table = 'reseña_texto';
    protected $primaryKey = 'id_reseña';

    protected $fillable = [
        'id_interaccion',
        'contenido_original',
        'puntuacion_sentimiento',
        'lenguaje',
        'topicos_extraidos',
    ];

    protected $casts = [
        'puntuacion_sentimiento' => 'float',
        'topicos_extraidos' => 'array',
    ];

    // Relación 1:1 Inversa: Pertenece a una Interacción U-D
    public function interaccionUD()
    {
        // La FK es id_interaccion, que referencia a id_interaccion en InteraccionUD
        return $this->belongsTo(InteraccionUD::class, 'id_interaccion', 'id_interaccion');
    }
}
