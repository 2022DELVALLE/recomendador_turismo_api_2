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
use App\Http\Controllers\RecomendacionFeedbackController;
use App\Http\Controllers\RouteController;

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
// Ruta específica para obtener el contexto actual
Route::get('contexto/actual', [ContextoController::class, 'obtenerContextoActual']);


// Rutas API para la gestión de Eventos y Festividades (Nodo E)
Route::apiResource('eventos', EventoFestividadController::class);

// Embeddings (Datos GNN) <-- Añadir este
Route::apiResource('embeddings', EmbeddingController::class)->except(['update']); // El update de un embedding suele ser un store con overwrite
Route::get('embeddings/search', [EmbeddingController::class, 'getByReference']);
Route::get('usuario/{id_usuario}/embedding/initial', [EmbeddingController::class, 'getInitialUserEmbedding']);

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



// Rutas de la aplicación de Recomendación GNN

// B2.1.1: Obtener el embedding más reciente del usuario (U₀, U₁, etc.)
// GET /api/usuario/{id_usuario}/embedding/initial
Route::get('usuario/{id_usuario}/embedding/initial', [EmbeddingController::class, 'getInitialUserEmbedding']);

// =====================================================================
// B2.1.2: Similitud de Embeddings (Recuperación de Recomendaciones Top-3)
// =====================================================================
// GET /api/usuario/{id_usuario}/recommendations
Route::get('usuario/{id_usuario}/recommendations', [EmbeddingController::class, 'calculateSimilarity']);

// B2.2.1: Propagación GNN - Calcula Similitud, Agrega y Genera el nuevo Embedding U₁
// POST /api/usuario/{id_usuario}/propagate
Route::post('usuario/{id_usuario}/propagate', [EmbeddingController::class, 'propagateAndAggregate']);


// Ruta de prueba temporal para el filtrado contextual
Route::get('/test/context-filter', [EmbeddingController::class, 'testContextFilter']);

/**
 *  B2.1.3
 */
// Rutas REST completas para la gestión de Embeddings
Route::apiResource('embeddings', EmbeddingController::class)->except(['update']);
Route::get('embeddings/reference', [EmbeddingController::class, 'getByReference']);


Route::get('explorar', [DestinoController::class, 'explorarDestinos']);





// Ruta para la Tarea B2.5.1: Registro de visualizaciones y ajuste de embedding
// URL: POST /api/usuario/{user_id}/interaccion_visualizacion
Route::post('usuario/{user_id}/interaccion_visualizacion', [RecomendacionFeedbackController::class, 'registrarVisualizacion']);


// =========================================================================
// RUTAS DE INTERACCIONES Y FEEDBACK DEL SISTEMA DE RECOMENDACIÓN (B3.3.4)
// Estas rutas llaman al controlador RecomendacionFeedbackController.
// =========================================================================

Route::group(['prefix' => 'usuario/{user_id}'], function () {

    // RUTA 1: Registro de Interacciones de Visualización (Implícita)
    // POST /api/usuario/{user_id}/interaccion_visualizacion
    // Recibe un array de 'destino_ids'.
    Route::post('interaccion_visualizacion', [
        RecomendacionFeedbackController::class,
        'registrarVisualizacion'
    ]);

    // RUTA 2: Registro de Interacción Explícita (LIKE, REVIEW, BOOKMARK)
    // POST /api/usuario/{user_id}/interaccion_explicita
    // Recibe 'id_destino', 'rating', 'tipo_interaccion'.
    Route::post('interaccion_explicita', [
        RecomendacionFeedbackController::class,
        'registrarInteraccionExplicita'
    ]);
});


Route::get('destinos/{id_destino}', [DestinoController::class, 'show']);

Route::get('/api/destinos/{id_destino}/reseñas', [DestinoController::class, 'obtenerReseñasPorDestino']);



// 1. Endpoint para generar y optimizar rutas (B3.4)
Route::post('/rutas/sugeridas', [RouteController::class, 'getSuggestedRoutes']);

// 2. Endpoint para guardar la ruta seleccionada por el usuario (B3.5)
Route::post('/rutas/guardar', [RouteController::class, 'saveRoute']);
