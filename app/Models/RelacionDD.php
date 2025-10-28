<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelacionDD extends Model
{
    use HasFactory;

    protected $table = 'relacion_destino_destino';

    // Indica que no hay ID autoincremental
    public $incrementing = false;

    // Define las claves primarias compuestas
    protected $primaryKey = ['id_destino_origen', 'id_destino_relacionado'];

    protected $fillable = [
        'id_destino_origen',
        'id_destino_relacionado',
        'tipo_relacion',
        'peso_relacion',
        'descripcion',
    ];

    // Necesario para que Laravel sepa qué columnas usar como PKs al insertar/actualizar
    protected function setKeysForSaveQuery($query)
    {
        $query
            ->where('id_destino_origen', '=', $this->attributes['id_destino_origen'])
            ->where('id_destino_relacionado', '=', $this->attributes['id_destino_relacionado']);
        return $query;
    }

    // Relación a Destino Origen
    public function destinoOrigen()
    {
        return $this->belongsTo(Destino::class, 'id_destino_origen', 'id_destino');
    }

    // Relación a Destino Relacionado
    public function destinoRelacionado()
    {
        return $this->belongsTo(Destino::class, 'id_destino_relacionado', 'id_destino');
    }
}
