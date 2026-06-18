<?php

namespace App\Notifications;

use App\Models\SubmissionRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionConfirmed extends Notification
{
    public function __construct(
        public readonly SubmissionRequest $submission
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('submissions.notifications.confirmation_subject', [
                'reference' => $this->submission->reference_code,
            ]))
            ->greeting('Estimado/a '.$this->submission->submitter_name.',')
            ->line(__('submissions.notifications.confirmation_body', [
                'reference' => $this->submission->reference_code,
            ]))
            ->salutation('Saludos,<br>El equipo de Axon');
    }
}
