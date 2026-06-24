<?php

namespace App\Exports;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class TasksExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly string $projectId) {}

    public function query(): Builder
    {
        return Task::query()
            ->whereHas('activity', fn (Builder $q) => $q->where('project_id', $this->projectId))
            ->with(['activity:id,name', 'assignees:id,name'])
            ->orderBy('tasks.created_at');
    }

    public function title(): string
    {
        return 'Tareas';
    }

    public function headings(): array
    {
        return [
            'Código',
            'Nombre',
            'Actividad',
            'Estado',
            'Prioridad',
            'Responsables',
            'Fecha inicio',
            'Fecha límite',
            'Horas estimadas',
            'Horas reales',
        ];
    }

    public function map($task): array
    {
        return [
            $task->code,
            $task->name,
            $task->activity?->name ?? '—',
            $task->status->getLabel(),
            $task->priority->getLabel(),
            $task->assignees->pluck('name')->join(', ') ?: '—',
            $task->start_date?->format('d/m/Y') ?? '—',
            $task->due_date?->format('d/m/Y') ?? '—',
            $task->estimated_hours,
            $task->actual_hours,
        ];
    }
}
