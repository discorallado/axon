<?php

use App\Http\Controllers\Api\FatExecutionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes para Sistema FAT
|--------------------------------------------------------------------------
|
| Documentación de endpoints disponibles para integración con móviles
| y sistemas externos.
|
| Prefijo: /api/fat
| Autenticación requerida: Sí (via Sanctum tokens)
|
*/

Route::prefix('fat')->middleware(['auth:sanctum'])->group(function () {
    
    // === Ejecuciones ===
    Route::get('/executions', [FatExecutionController::class, 'index'])
        ->name('api.fat.executions.index');
    
    Route::get('/executions/{execution}', [FatExecutionController::class, 'show'])
        ->name('api.fat.executions.show');
    
    Route::post('/executions/{execution}/submit-review', [FatExecutionController::class, 'submitForReview'])
        ->name('api.fat.executions.submit-review');
    
    // === Revisiones ===
    Route::get('/revisions/{revision}/items', [FatExecutionController::class, 'getRevisionItems'])
        ->name('api.fat.revisions.items');
    
    // === Resultados de Items ===
    Route::post('/revisions/{revision}/items/{itemId}/results', [FatExecutionController::class, 'saveResult'])
        ->name('api.fat.results.save');
    
    Route::get('/results/{result}/history', [FatExecutionController::class, 'getResultHistory'])
        ->name('api.fat.results.history');
    
    // === Evidencias ===
    Route::post('/results/{resultId}/evidence', [FatExecutionController::class, 'uploadEvidence'])
        ->name('api.fat.evidence.upload');
});

/*
|--------------------------------------------------------------------------
| Endpoints Disponibles
|--------------------------------------------------------------------------
|
| GET    /api/fat/executions                    - Listar ejecuciones
| GET    /api/fat/executions/{id}               - Ver detalle de ejecución
| POST   /api/fat/executions/{id}/submit-review - Enviar a revisión
| GET    /api/fat/revisions/{id}/items          - Obtener items de revisión
| POST   /api/fat/revisions/{id}/items/{itemId}/results - Guardar resultado
| GET    /api/fat/results/{id}/history          - Historial de cambios
| POST   /api/fat/results/{id}/evidence         - Subir evidencia
|
|--------------------------------------------------------------------------
| Ejemplo de Uso con cURL
|--------------------------------------------------------------------------
|
| # Listar ejecuciones
| curl -X GET "http://localhost/api/fat/executions" \
|   -H "Authorization: Bearer {token}" \
|   -H "Accept: application/json"
|
| # Guardar resultado
| curl -X POST "http://localhost/api/fat/revisions/1/items/5/results" \
|   -H "Authorization: Bearer {token}" \
|   -H "Content-Type: application/json" \
|   -d '{"result_value": "C", "observations": "Todo correcto"}'
|
| # Subir evidencia
| curl -X POST "http://localhost/api/fat/results/1/evidence" \
|   -H "Authorization: Bearer {token}" \
|   -F "evidence=@/path/to/photo.jpg"
|
*/
