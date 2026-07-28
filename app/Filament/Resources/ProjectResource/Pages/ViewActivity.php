<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Activity;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ViewActivity extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.view-activity';

    public Activity $activity;

    public ?string $focusTaskId = null;

    public function mount(int|string $record, string $activity): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorize('view', $this->record);

        $this->activity = Activity::withoutGlobalScopes()
            ->where('id', $activity)
            ->where('project_id', $this->record->id)
            ->with(['tasks' => fn ($q) => $q->with('assignees')->orderBy('order')])
            ->firstOrFail();

        $this->focusTaskId = request()->query('focus');
    }

    public function getTitle(): string
    {
        return $this->activity->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_project')
                ->label(__('projects.views.back_to_project'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ProjectResource::getUrl('view', ['record' => $this->record])),

            Action::make('kanban')
                ->label(__('projects.views.kanban'))
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(ProjectResource::getUrl('kanban', ['record' => $this->record])),
        ];
    }
}
