<?php

namespace App\Notifications;

use App\Models\SubmissionRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSubmissionReceived extends Notification
{
    public function __construct(
        public readonly SubmissionRequest $submission
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'reference_code' => $this->submission->reference_code,
            'submitter_name' => $this->submission->submitter_name,
            'submitter_company' => $this->submission->submitter_company,
            'url' => route('filament.admin.resources.submission-requests.view', $this->submission),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('submissions.notifications.new_submission_subject', [
                'reference' => $this->submission->reference_code,
            ]))
            ->greeting('Hola, '.$notifiable->name)
            ->line(__('submissions.notifications.new_submission_body', [
                'reference' => $this->submission->reference_code,
                'name' => $this->submission->submitter_name,
                'company' => $this->submission->submitter_company ?? 'sin empresa',
            ]))
            ->action('Ver solicitud', route('filament.admin.resources.submission-requests.view', $this->submission))
            ->salutation('Axon PMIS');
    }
}
