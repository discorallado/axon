<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Models\Task;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class GanttChart extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.gantt-chart';

    protected static ?string $title = 'Gantt';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    public array $ganttTasks = [];

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->loadGanttData();
    }

    public function loadGanttData(): void
    {
        $this->ganttTasks = $this->record
            ->tasks()
            ->with('activity:id,name,order')
            ->whereNotNull('start_date')
            ->whereNotNull('due_date')
            ->orderBy('activities.order')
            ->orderBy('tasks.created_at')
            ->get()
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'name' => $task->name,
                'start' => $task->start_date->format('Y-m-d'),
                'end' => $task->due_date->format('Y-m-d'),
                'progress' => $task->status->isCompleted() ? 100 : 0,
                'custom_class' => 'gantt-task-'.$task->status->value,
                'group' => $task->activity?->name ?? '—',
            ])
            ->toArray();
    }
}
