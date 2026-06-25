<?php

namespace App\Notifications;

use App\Models\SubmissionRequest;
use Illuminate\Notifications\Notification;

class SubmissionApprovedNotification extends Notification
{
    public function __construct(
        public readonly SubmissionRequest $submission
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'reference_code' => $this->submission->reference_code,
            'submitter_name' => $this->submission->submitter_name,
            'submitter_company' => $this->submission->submitter_company,
            'url' => route('filament.admin.resources.submission-requests.view', $this->submission),
            'action' => 'create_project',
        ];
    }
}
