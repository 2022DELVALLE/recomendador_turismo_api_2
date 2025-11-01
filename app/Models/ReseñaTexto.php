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
        'vector_reseña', // 🛑 CAMBIO CLAVE: Columna para guardar el vector R^0
    ];

    protected $casts = [
        'puntuacion_sentimiento' => 'float',
        'topicos_extraidos' => 'array',
    ];

    // =========================================================
    // ACCESOR CLAVE: Genera el texto para la vectorización (Vector R^0)
    // =========================================================

    /**
     * Genera un string a partir del contenido original de la reseña
     * para que el modelo S-BERT lo vectorice (Vector R^0).
     *
     * @return string
     */
    public function getTextoParaVectorizacionAttribute(): string
    {
        // La fuente principal del vector es el contenido textual original.
        return $this->contenido_original ?? '';
    }

    // Relación 1:1 Inversa: Pertenece a una Interacción U-D
    public function interaccionUD()
    {
        // La FK es id_interaccion, que referencia a id_interaccion en InteraccionUD
        return $this->belongsTo(InteraccionUD::class, 'id_interaccion', 'id_interaccion');
    }
}
