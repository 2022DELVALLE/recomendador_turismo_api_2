<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoFestividad extends Model
{
    use HasFactory;

    protected $table = 'evento_festividades'; // Asumo el plural, revisa si tu tabla se llama 'evento_festividad'
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

    // =========================================================
    // ACCESOR CLAVE: Genera el texto para la vectorización (Vector E^0)
    // =========================================================

    /**
     * Genera un string combinado con la información más importante del evento
     * para que el modelo S-BERT lo vectorice (Vector E^0).
     *
     * @return string
     */
    public function getTextoParaVectorizacionAttribute(): string
    {
        // Combinamos los campos que describen el evento
        $texto = "Evento o Festividad: {$this->nombre_evento}. ";
        $texto .= "Tipo: {$this->tipo_evento}. ";
        $texto .= "Palabras clave: {$this->palabras_clave}. ";
        $texto .= "Valor cultural percibido (escala 1-10): {$this->valor_cultural}. ";
        $texto .= "Se lleva a cabo entre {$this->fecha_inicio?->format('Y-m-d')} y {$this->fecha_fin?->format('Y-m-d')}.";

        return $texto;
    }

    // Relaciones
    public function lugarAsociado()
    {
        return $this->belongsTo(Destino::class, 'lugar_asociado', 'id_destino');
    }
}
