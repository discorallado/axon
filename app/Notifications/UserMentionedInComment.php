<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserMentionedInComment extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $mentionedByName,
        private readonly string $contextLabel,
        private readonly string $contextUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.mention.subject', ['name' => $this->mentionedByName]))
            ->line(__('notifications.mention.line', ['name' => $this->mentionedByName, 'context' => $this->contextLabel]))
            ->action(__('notifications.mention.action'), $this->contextUrl);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'mentioned_by' => $this->mentionedByName,
            'context_label' => $this->contextLabel,
            'context_url' => $this->contextUrl,
        ];
    }
}
