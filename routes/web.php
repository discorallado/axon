<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\SubmissionPdfController;
use App\Livewire\PublicFormWizard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('solicitud.tableros');
});

Route::get('/solicitud', PublicFormWizard::class)
    ->middleware(['throttle:public-form'])
    ->name('solicitud.tableros');

Route::get('/solicitud/editar/{submission}', PublicFormWizard::class)
    ->middleware(['signed', 'throttle:public-form'])
    ->name('solicitud.editar');

Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
    ->middleware(['auth'])
    ->name('attachments.download');

Route::get('/solicitudes/{submission}/pdf', [SubmissionPdfController::class, 'download'])
    ->middleware(['auth'])
    ->name('submissions.pdf');
