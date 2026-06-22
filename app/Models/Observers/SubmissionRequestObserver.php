<?php

namespace App\Models\Observers;

use App\Models\Attachment;
use App\Models\SubmissionRequest;
use Illuminate\Support\Facades\Storage;

class SubmissionRequestObserver
{
    /**
     * Al eliminar permanentemente una solicitud, borra en cascada los archivos
     * adjuntos de disco de la solicitud y de todos sus ítems.
     */
    public function forceDeleting(SubmissionRequest $submission): void
    {
        // Items primero (dispara SubmissionItemObserver::deleting via each->forceDelete())
        $submission->items()->withTrashed()->get()->each->forceDelete();

        // Adjuntos directos de la solicitud
        $submission->attachments()->each(function (Attachment $attachment) {
            if (Storage::disk($attachment->disk)->exists($attachment->path)) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
            $attachment->delete();
        });
    }
}
