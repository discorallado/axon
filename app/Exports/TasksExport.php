<?php

namespace App\Exports;

use App\Models\Project;
use App\Models\Task;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TasksExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly Project $project
    ) {}

    public function query()
    {
        return Task::query()
            ->whereHas('activity', fn ($q) => $q->where('project_id', $this->project->id))
            ->with(['activity', 'assignees'])
            ->orderBy('created_at');
    }

    public function headings(): array
    {
        return [
            __('tasks.fields.code'),
            __('tasks.fields.name'),
            __('tasks.fields.activity'),
            __('tasks.fields.status'),
            __('tasks.fields.priority'),
            __('tasks.fields.assignees'),
            __('tasks.fields.start_date'),
            __('tasks.fields.due_date'),
            __('tasks.fields.estimated_hours'),
            __('tasks.fields.actual_hours'),
        ];
    }

    public function map($task): array
    {
        return [
            $task->code,
            $task->name,
            $task->activity->name,
            $task->status->getLabel(),
            $task->priority->getLabel(),
            $task->assignees->pluck('name')->implode(', '),
            $task->start_date?->format('d/m/Y'),
            $task->due_date?->format('d/m/Y'),
            $task->estimated_hours,
            $task->actual_hours,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
