<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    // ELIMINAMOS 'public $incrementing = false;'

    protected $fillable = [
        // ELIMINAMOS 'id_usuario' de fillable
        'nombre',
        'edad',
        'genero',
        'origen_geografico',
        'tipo_turista',
        'nivel_gasto',
        'preferencias_texto',
        'dispositivo_acceso',
    ];
    // Opcional: Define las relaciones que tendrá (para futuras interacciones)
    public function interaccionesDestino()
    {
        return $this->hasMany(InteraccionUD::class, 'id_usuario', 'id_usuario');
    }

    // =========================================================
    // ACCESOR CLAVE: Genera el texto para la vectorización (Vector P^0)
    // =========================================================

    /**
     * Genera un string combinado con la información más importante del destino
     * para que el modelo S-BERT lo vectorice (Vector P^0).
     *
     * @return string
     */
    public function getTextoParaVectorizacionAttribute(): string
    {
        // 1. Identificación y Descripción Principal
        $texto = "Destino turístico: {$this->nombre_destino}. ";
        $texto .= "Categoría principal: {$this->categoria}. Subcategoría: {$this->subcategoria}. ";

        if (!empty($this->descripcion_corta)) {
            $texto .= "Descripción clave: {$this->descripcion_corta}. ";
        }
        // 2. Contexto de Ubicación y Logística
        if (!empty($this->altitud)) {
            $texto .= "Ubicado a {$this->altitud} metros de altitud. ";
        }
        if (!empty($this->dificultad_acceso)) {
            $texto .= "Dificultad de acceso: {$this->dificultad_acceso}. ";
        }
        if (!empty($this->tiempo_visita_promedio)) {
            $texto .= "El tiempo de visita recomendado es de {$this->tiempo_visita_promedio} horas. ";
        }
        // 3. Contexto Social y Financiero
        if (!empty($this->afluencia_promedio)) {
            $texto .= "Tiene una afluencia de público: {$this->afluencia_promedio}. ";
        }
        if (!empty($this->costo_promedio)) {
            $texto .= "El costo promedio de visita es de {$this->costo_promedio} soles. ";
        }
        // 4. Contexto Temporal y Temático
        if (!empty($this->temporada_alta)) {
            $texto .= "Su mejor temporada para visitar es: {$this->temporada_alta}. ";
        }
        if (!empty($this->horario_relevancia)) {
            $texto .= "El horario de mayor relevancia es: {$this->horario_relevancia}. ";
        }
        if (!empty($this->etiquetas_tematicas)) {
            $texto .= "Etiquetas temáticas: {$this->etiquetas_tematicas}. ";
        }
        // 5. Compatibilidad Climática (Asumiendo que es un array de strings o se convierte bien)
        if (!empty($this->compatibilidad_clima) && is_array($this->compatibilidad_clima)) {
            $climas = implode(', ', $this->compatibilidad_clima);
            $texto .= "Este destino es compatible con climas: {$climas}. ";
        }
        // Normalizar y limpiar espacios extra (esto es una buena práctica)
        return trim(preg_replace('/\s\s+/', ' ', $texto));
    }
}
