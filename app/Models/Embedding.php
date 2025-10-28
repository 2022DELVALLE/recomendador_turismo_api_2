<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Embedding extends Model
{
    use HasFactory;

    protected $table = 'embeddings';
    protected $primaryKey = 'id_embedding';

    protected $fillable = [
        'tipo_nodo',
        'id_referencia',
        'vector_embedding',
        'fecha_generacion',
    ];

    protected $casts = [
        'fecha_generacion' => 'date',
        // Opcional: castear el vector_embedding a un array/JSON
        // 'vector_embedding' => 'array', 
    ];

    // Relaciones Polimórficas (Ejemplo de cómo podrías relacionarlo)
    // Aunque no es una relación polimórfica estándar de Laravel, se define la lógica
    public function nodo()
    {
        // El tipo de nodo decide a qué tabla referenciar
        if ($this->tipo_nodo === 'U') {
            return $this->belongsTo(Usuario::class, 'id_referencia', 'id_usuario');
        } elseif ($this->tipo_nodo === 'P') {
            return $this->belongsTo(Destino::class, 'id_referencia', 'id_destino');
        }
        // ... (otros tipos C, E)
        return null; // O lanza una excepción
    }
}
