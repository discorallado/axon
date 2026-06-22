<?php

namespace App\Http\Controllers;

use App\Models\SubmissionRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class SubmissionPdfController extends Controller
{
    public function download(SubmissionRequest $submission): Response
    {
        abort_unless(
            auth()->check() && auth()->user()->organization_id === $submission->organization_id,
            403
        );

        $submission->load(['items.attachments', 'attachments', 'assignee', 'statusHistories.changedBy']);

        $pdf = Pdf::loadView('pdf.submission-report', ['submission' => $submission])
            ->setPaper('letter', 'portrait');

        $filename = 'solicitud-'.$submission->reference_code.'.pdf';

        return $pdf->stream($filename);
    }
}
