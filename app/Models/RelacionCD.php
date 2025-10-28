<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelacionCD extends Model
{
    use HasFactory;

    protected $table = 'relacion_contexto_destino';

    public $incrementing = false;

    protected $primaryKey = ['id_contexto', 'id_destino'];

    protected $fillable = [
        'id_contexto',
        'id_destino',
        'impacto_clima',
        'peso_contexto',
        'es_accesible',
    ];

    protected $casts = [
        'es_accesible' => 'boolean',
    ];

    // Configuración para usar la clave compuesta
    protected function setKeysForSaveQuery($query)
    {
        $query
            ->where('id_contexto', '=', $this->attributes['id_contexto'])
            ->where('id_destino', '=', $this->attributes['id_destino']);
        return $query;
    }

    // Relación a Contexto
    public function contexto()
    {
        return $this->belongsTo(Contexto::class, 'id_contexto', 'id_contexto');
    }

    // Relación a Destino
    public function destino()
    {
        return $this->belongsTo(Destino::class, 'id_destino', 'id_destino');
    }
}
