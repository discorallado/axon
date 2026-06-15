<?php

use App\Livewire\PublicFormWizard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('solicitud.tableros');
});

Route::get('/solicitud', PublicFormWizard::class)
    ->middleware(['throttle:public-form'])
    ->name('solicitud.tableros');
