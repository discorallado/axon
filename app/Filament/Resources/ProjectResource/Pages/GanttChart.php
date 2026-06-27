<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class GanttChart extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.gantt-chart';

    public string $viewMode = 'Week';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorize('view', $this->record);
    }

    public function getTitle(): string
    {
        return __('projects.gantt.title');
    }

    public function getGanttTasks(): array
    {
        return $this->record
            ->activities()
            ->with(['tasks' => fn ($q) => $q->whereNotNull('start_date')->whereNotNull('due_date')])
            ->get()
            ->flatMap(function ($activity) {
                return $activity->tasks->map(fn ($task) => [
                    'id' => $task->id,
                    'name' => $task->code.' '.$task->name,
                    'start' => $task->start_date->format('Y-m-d'),
                    'end' => $task->due_date->format('Y-m-d'),
                    'progress' => $task->status->isCompleted() ? 100 : ($task->status->value === 'en_progreso' ? 50 : 0),
                    'custom_class' => 'gantt-task-'.$task->status->value,
                ]);
            })
            ->values()
            ->all();
    }
}
