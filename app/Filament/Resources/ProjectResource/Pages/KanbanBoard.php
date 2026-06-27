<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Filament\Resources\ProjectResource;
use App\Models\Task;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Renderless;

class KanbanBoard extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.kanban-board';

    public ?string $filterActivity = null;

    public ?string $filterPriority = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
    }

    protected function authorizeAccess(): void
    {
        $this->authorize('view', $this->record);
    }

    public function getTitle(): string
    {
        return __('projects.kanban.title');
    }

    public function getColumns(): array
    {
        $query = Task::query()
            ->whereHas('activity', fn ($q) => $q->where('project_id', $this->record->id))
            ->with(['activity', 'assignees']);

        if ($this->filterActivity) {
            $query->where('activity_id', $this->filterActivity);
        }

        if ($this->filterPriority) {
            $query->where('priority', $this->filterPriority);
        }

        $grouped = $query->get()->groupBy(fn ($task) => $task->status->value);

        return collect(TaskStatus::cases())->map(fn ($status) => [
            'status' => $status,
            'tasks' => $grouped->get($status->value, collect()),
        ])->all();
    }

    public function getActivitiesForFilter(): array
    {
        return $this->record->activities()
            ->orderBy('order')
            ->pluck('name', 'id')
            ->all();
    }

    public function getPrioritiesForFilter(): array
    {
        return collect(TaskPriority::cases())
            ->mapWithKeys(fn ($p) => [$p->value => $p->getLabel()])
            ->all();
    }

    #[Renderless]
    public function updateTaskStatus(string $taskId, string $status): void
    {
        $task = Task::findOrFail($taskId);

        $this->authorize('update', $task);

        $task->update(['status' => TaskStatus::from($status)]);
    }
}
