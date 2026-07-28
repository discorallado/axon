<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\Task;
use App\Models\User;
use App\Notifications\UserMentionedInComment;
use Filament\Notifications\Notification;
use Parallax\FilamentComments\Livewire\CommentsComponent;
use Parallax\FilamentComments\Models\FilamentComment;

class CommentsWithMentions extends CommentsComponent
{
    public function create(): void
    {
        if (! auth()->user()->can('create', config('filament-comments.comment_model'))) {
            return;
        }

        $this->form->validate();

        $data = $this->form->getState();

        $comment = $this->record->filamentComments()->create([
            'subject_type' => $this->record->getMorphClass(),
            'comment' => $data['comment'],
            'user_id' => auth()->id(),
        ]);

        $this->dispatchMentionNotifications($comment);

        Notification::make()
            ->title(__('filament-comments::filament-comments.notifications.created'))
            ->success()
            ->send();

        $this->form->fill();
    }

    private function dispatchMentionNotifications(FilamentComment $comment): void
    {
        $html = $comment->comment;

        // Extract data-user-id from Tribute.js mention spans:
        // <span class="mention" data-user-id="..." data-user-name="...">@Name</span>
        if (! preg_match_all('/data-user-id="([^"]+)"/', $html, $matches)) {
            return;
        }

        $mentionedIds = array_unique($matches[1]);
        $authorId = auth()->id();

        $contextLabel = $this->record->name ?? class_basename($this->record);
        $contextUrl = $this->resolveContextUrl();

        User::whereIn('id', $mentionedIds)
            ->where('id', '!=', $authorId)
            ->get()
            ->each(fn (User $user) => $user->notify(
                new UserMentionedInComment(
                    mentionedByName: auth()->user()->name,
                    contextLabel: $contextLabel,
                    contextUrl: $contextUrl,
                )
            ));
    }

    private function resolveContextUrl(): string
    {
        $model = $this->record;

        return match (true) {
            $model instanceof Activity => route(
                'filament.admin.resources.projects.view-activity',
                ['record' => $model->project_id, 'activity' => $model->id]
            ),
            $model instanceof Task => route(
                'filament.admin.resources.projects.view-activity',
                ['record' => $model->activity->project_id, 'activity' => $model->activity_id]
            ).'?focus='.$model->id,
            default => url()->current(),
        };
    }
}
