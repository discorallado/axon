<?php

use App\Http\Controllers\AttachmentController;
use App\Livewire\PublicFormWizard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('solicitud.tableros');
});

Route::get('/solicitud', PublicFormWizard::class)
    ->middleware(['throttle:public-form'])
    ->name('solicitud.tableros');

Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
    ->middleware(['auth'])
    ->name('attachments.download');
