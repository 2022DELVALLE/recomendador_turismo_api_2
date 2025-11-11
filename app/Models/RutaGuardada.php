<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RutaGuardada extends Model
{
    use HasFactory;

    // 1. Nombre de la tabla
    protected $table = 'ruta_guardada';

    // 2. Clave primaria (Laravel asume 'id', pero es mejor ser explícito)
    protected $primaryKey = 'id_ruta_guardada';

    // 3. Campos que pueden ser asignados masivamente (Mass Assignment)
    protected $fillable = [
        'id_usuario',
        'nombre_ruta',
        'destinos_json', // Guardará el array de destinos como JSON
        'afinidad_total',
        'filtros_aplicados',
        'fecha_guardado',
    ];

    // 4. Conversión de tipos (Casting)
    // Esto asegura que 'destinos_json' y 'filtros_aplicados' se guarden y recuperen como arrays/objetos PHP
    protected $casts = [
        'destinos_json' => 'array',
        'filtros_aplicados' => 'array',
        'afinidad_total' => 'float',
    ];

    public $timestamps = false; // Deshabilitamos 'created_at' y 'updated_at' si no los usas

    // Opcional: Relación con el usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
