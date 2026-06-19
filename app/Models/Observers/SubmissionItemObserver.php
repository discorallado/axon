<?php

namespace App\Models\Observers;

use App\Models\Attachment;
use App\Models\SubmissionItem;
use Illuminate\Support\Facades\Storage;

class SubmissionItemObserver
{
    /**
     * Al eliminar un ítem (soft o hard), borra sus archivos del disco.
     */
    public function deleting(SubmissionItem $item): void
    {
        $item->attachments()->each(function (Attachment $attachment) {
            if (Storage::disk($attachment->disk)->exists($attachment->path)) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
            $attachment->delete();
        });
    }
}
