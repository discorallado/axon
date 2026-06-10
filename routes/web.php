<?php

use App\Http\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;

// Formularios públicos — sin autenticación
Route::prefix('f')->name('public.form.')->group(function () {
    Route::get('/{slug}', [PublicFormController::class, 'show'])
        ->name('show');

    Route::post('/{slug}', [PublicFormController::class, 'submit'])
        ->middleware(['throttle:public-form'])
        ->name('submit');

    Route::get('/{slug}/gracias', [PublicFormController::class, 'thanks'])
        ->name('thanks');
});
