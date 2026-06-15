<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download(Attachment $attachment): Response
    {
        abort_unless(
            auth()->check() && auth()->user()->organization_id === $attachment->organization_id,
            403
        );

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return response(
            Storage::disk($attachment->disk)->get($attachment->path),
            200,
            [
                'Content-Type' => $attachment->mime_type ?? 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"',
            ]
        );
    }
}
