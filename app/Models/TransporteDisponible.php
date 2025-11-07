<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransporteDisponible extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'transporte_disponible';

    // Clave primaria
    protected $primaryKey = 'id_transporte';

    // Columnas que pueden ser asignadas masivamente (mass assignable)
    protected $fillable = [
        'tipo_transporte',
        'costo_base_minimo',
        'horario_disponibilidad',
        'activo',
    ];

    // Indica que las claves primarias no son autoincrementales (aunque en este caso sí lo son, se mantiene por convención)
    public $incrementing = true;

    // Los atributos deben ser de un tipo específico (casting)
    protected $casts = [
        'costo_base_minimo' => 'decimal:2',
        'activo' => 'boolean',
    ];
}
