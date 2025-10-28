<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\DestinoController;
use App\Http\Controllers\ContextoController;
use App\Http\Controllers\EventoFestividadController;
use App\Http\Controllers\EmbeddingController;
use App\Http\Controllers\InteraccionUDController;
use App\Http\Controllers\InteraccionUCController;
use App\Http\Controllers\InteraccionUEController;
use App\Http\Controllers\RelacionDDController;
use App\Http\Controllers\RelacionDEController;
use App\Http\Controllers\RelacionCDController;
use App\Http\Controllers\PrediccionRatingController;
use App\Http\Controllers\ReseñaTextoController;

// Rutas API para la gestión de Usuarios (Nodo U)
// GET /api/usuarios           -> index
// POST /api/usuarios          -> store
// GET /api/usuarios/{usuario} -> show
// PUT/PATCH /api/usuarios/{usuario} -> update
// DELETE /api/usuarios/{usuario} -> destroy

Route::apiResource('usuarios', UsuarioController::class);
// Rutas API para la gestión de Destinos (Nodo P)
Route::apiResource('destinos', DestinoController::class);
// Rutas API para la gestión de Contextos (Nodo C) 
Route::apiResource('contextos', ContextoController::class);
// Rutas API para la gestión de Eventos y Festividades (Nodo E)
Route::apiResource('eventos', EventoFestividadController::class);

// Embeddings (Datos GNN) <-- Añadir este
Route::apiResource('embeddings', EmbeddingController::class)->except(['update']); // El update de un embedding suele ser un store con overwrite
Route::get('embeddings/search', [EmbeddingController::class, 'getByReference']);

// Rutas API para la gestión de Interacciones U-D (Arista U–P) <-- Añadir este
// Usamos el nombre 'interacciones/ud' para diferenciar de otras interacciones
Route::apiResource('interacciones/ud', InteraccionUDController::class, [
    'parameters' => ['ud' => 'id_interaccion']
]);

// Rutas API para la gestión de Interacciones U-C (Arista U–C) <-- Añadir este
Route::apiResource('interacciones/uc', InteraccionUCController::class, [
    'parameters' => ['uc' => 'id_uc']
]);

// Rutas API para la gestión de Interacciones U-E (Arista U–E) <-- Añadir este
Route::apiResource('interacciones/ue', InteraccionUEController::class, [
    'parameters' => ['ue' => 'id_ue']
]);

//Rutas API para la gestión de Relaciones D-D (Arista P–P) <-- Añadir este
// Usamos solo index y store, y una ruta DELETE personalizada
Route::get('relaciones/dd', [RelacionDDController::class, 'index']);
Route::post('relaciones/dd', [RelacionDDController::class, 'store']);
Route::delete('relaciones/dd/{origenId}/{relacionadoId}', [RelacionDDController::class, 'destroyByKeys']);

// Rutas API para la gestión de Relaciones D-E (Arista P–E) <-- Añadir este
Route::get('relaciones/de', [RelacionDEController::class, 'index']);
Route::post('relaciones/de', [RelacionDEController::class, 'store']);
Route::delete('relaciones/de/{destinoId}/{eventoId}', [RelacionDEController::class, 'destroyByKeys']);

// Rutas API para la gestión de Relaciones C-D (Arista C–P) <-- Añadir este
Route::get('relaciones/cd', [RelacionCDController::class, 'index']);
Route::post('relaciones/cd', [RelacionCDController::class, 'store']);
Route::delete('relaciones/cd/{contextoId}/{destinoId}', [RelacionCDController::class, 'destroyByKeys']);

// Predicciones de Rating (Output del GNN)
Route::apiResource('predicciones', PrediccionRatingController::class);

// Reseñas de Texto (Auxiliar de Interacción)
Route::apiResource('reviews', ReseñaTextoController::class);