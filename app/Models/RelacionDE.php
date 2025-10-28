<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelacionDE extends Model
{
    use HasFactory;

    protected $table = 'relacion_destino_evento';

    public $incrementing = false;

    protected $primaryKey = ['id_destino', 'id_evento'];

    protected $fillable = [
        'id_destino',
        'id_evento',
        'tipo_vinculo',
        'impacto_turistico',
    ];

    // Configuración para usar la clave compuesta
    protected function setKeysForSaveQuery($query)
    {
        $query
            ->where('id_destino', '=', $this->attributes['id_destino'])
            ->where('id_evento', '=', $this->attributes['id_evento']);
        return $query;
    }

    // Relación a Destino
    public function destino()
    {
        return $this->belongsTo(Destino::class, 'id_destino', 'id_destino');
    }

    // Relación a Evento
    public function evento()
    {
        return $this->belongsTo(EventoFestividad::class, 'id_evento', 'id_evento');
    }
}
