<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Enums\TaskStatus;
use App\Filament\Resources\ProjectResource;
use App\Models\Activity;
use App\Models\Task;
use App\Models\TaskLink;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Livewire\Attributes\Renderless;

class GanttChart extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.resources.project-resource.pages.gantt-chart';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorize('view', $this->record);
    }

    public function getTitle(): string
    {
        return __('projects.gantt.title');
    }

    /** Peso de avance por estado Kanban (0–1). */
    private function taskProgress(Task $task): float
    {
        return match ($task->status) {
            TaskStatus::Completada => 1.0,
            TaskStatus::EnRevision => 0.75,
            TaskStatus::EnProgreso => 0.5,
            default                => 0.0,
        };
    }

    /** Duración en días de una tarea (mínimo 1). */
    private function taskDays(Task $task): int
    {
        if ($task->start_date && $task->due_date) {
            return max(1, $task->start_date->diffInDays($task->due_date) + 1);
        }

        return 1;
    }

    /**
     * Estructura de datos para dhtmlxGantt.
     * Actividades → filas resumen tipo "project" (no arrastrables, con % calculado).
     * Tareas      → filas hijo con barra y % individual.
     * Links       → dependencias almacenadas en task_links.
     */
    public function getGanttData(): array
    {
        $activities = $this->record
            ->activities()
            ->with(['tasks' => fn ($q) => $q->orderBy('order')])
            ->orderBy('order')
            ->get();

        $rows = [];

        foreach ($activities as $activity) {
            $tasks = $activity->tasks;

            // Promedio ponderado por duración de cada tarea
            $totalDays   = $tasks->sum(fn ($t) => $this->taskDays($t));
            $actProgress = $totalDays > 0
                ? round($tasks->sum(fn ($t) => $this->taskDays($t) * $this->taskProgress($t)) / $totalDays, 2)
                : 0;

            // Fechas de actividad calculadas desde las tareas (min inicio / max término)
            $actStart = $tasks->filter(fn ($t) => $t->start_date)
                ->sortBy(fn ($t) => $t->start_date->timestamp)
                ->first()?->start_date ?? ($activity->start_date ?? now());

            $actEnd = $tasks->filter(fn ($t) => $t->due_date)
                ->sortByDesc(fn ($t) => $t->due_date->timestamp)
                ->first()?->due_date ?? ($activity->end_date ?? now()->addDays(7));

            $rows[] = [
                'id'         => 'act-'.$activity->id,
                'text'       => $activity->name,
                'start_date' => $actStart->format('Y-m-d'),
                'end_date'   => $actEnd->format('Y-m-d'),
                'progress'   => $actProgress,
                'open'       => true,
                'type'       => 'project',
                'readonly'   => true,
            ];

            foreach ($tasks as $task) {
                $start = $task->start_date ?? now();
                $end   = $task->due_date   ?? now()->addDays(3);

                $rows[] = [
                    'id'          => $task->id,
                    'text'        => $task->name,
                    'description' => $task->description ?? '',
                    'start_date'  => $start->format('Y-m-d'),
                    'end_date'    => $end->format('Y-m-d'),
                    'progress'    => $this->taskProgress($task),
                    'parent'      => 'act-'.$activity->id,
                    'readonly'    => false,
                ];
            }
        }

        $links = TaskLink::where('project_id', $this->record->id)
            ->get()
            ->map(fn ($l) => [
                'id'     => $l->id,
                'source' => $l->source_id,
                'target' => $l->target_id,
                'type'   => $l->type,
            ])
            ->values()
            ->all();

        return ['data' => $rows, 'links' => $links];
    }

    // ── Actualizar fechas al arrastrar barra ───────────────────────────────────

    #[Renderless]
    public function updateTaskDates(string $taskId, string $startDate, string $endDate): void
    {
        $task = Task::withoutGlobalScopes()
            ->whereHas('activity', fn ($q) => $q->where('project_id', $this->record->id))
            ->findOrFail($taskId);

        $this->authorize('update', $task);

        $task->update(['start_date' => $startDate, 'due_date' => $endDate]);
    }

    // ── Persistir nuevo orden tras drag-to-reorder ─────────────────────────────

    #[Renderless]
    public function persistOrder(array $activityIds, array $taskOrders): void
    {
        $this->authorize('update', $this->record);

        foreach ($activityIds as $index => $id) {
            Activity::withoutGlobalScopes()
                ->where('id', $id)
                ->where('project_id', $this->record->id)
                ->update(['order' => $index + 1]);
        }

        // $taskOrders = [['activityId' => ..., 'taskIds' => [...]], ...]
        foreach ($taskOrders as $group) {
            foreach ($group['taskIds'] as $index => $taskId) {
                Task::withoutGlobalScopes()
                    ->where('id', $taskId)
                    ->where('activity_id', $group['activityId'])
                    ->update(['order' => $index + 1]);
            }
        }
    }

    // ── Dependencias / links ───────────────────────────────────────────────────

    #[Renderless]
    public function addLink(string $source, string $target, int $type): string
    {
        $this->authorize('update', $this->record);

        $link = TaskLink::create([
            'organization_id' => $this->record->organization_id,
            'project_id'      => $this->record->id,
            'source_id'       => $source,
            'target_id'       => $target,
            'type'            => $type,
        ]);

        return $link->id;
    }

    #[Renderless]
    public function deleteLink(string $linkId): void
    {
        $this->authorize('update', $this->record);

        TaskLink::where('project_id', $this->record->id)
            ->findOrFail($linkId)
            ->delete();
    }

    // ── Editar título, descripción y fechas desde el modal ────────────────────

    #[Renderless]
    public function updateTaskDetails(string $taskId, string $title, string $description, string $startDate, string $endDate): void
    {
        $task = Task::withoutGlobalScopes()
            ->whereHas('activity', fn ($q) => $q->where('project_id', $this->record->id))
            ->findOrFail($taskId);

        $this->authorize('update', $task);

        $task->update([
            'name'        => $title,
            'description' => $description ?: null,
            'start_date'  => $startDate ?: null,
            'due_date'    => $endDate   ?: null,
        ]);
    }

    // ── Actualización sin re-render completo ───────────────────────────────────

    #[Renderless]
    public function refreshData(): void
    {
        $this->dispatch('gantt:refresh', $this->getGanttData());
    }
}
