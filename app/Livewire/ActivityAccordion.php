<?php

namespace App\Livewire;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Illuminate\View\View;
use Livewire\Component;

class ActivityAccordion extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public string $projectId;

    // -------------------------------------------------------------------------
    // Reordering
    // -------------------------------------------------------------------------

    public function reorderActivities(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Activity::withoutGlobalScopes()
                ->where('id', $id)
                ->where('project_id', $this->projectId)
                ->update(['order' => $index + 1]);
        }
    }

    public function reorderTasks(array $orderedIds, string $activityId): void
    {
        Activity::withoutGlobalScopes()
            ->where('id', $activityId)
            ->where('project_id', $this->projectId)
            ->firstOrFail();

        foreach ($orderedIds as $index => $id) {
            Task::withoutGlobalScopes()
                ->whereHas('activity', fn ($q) => $q->where('project_id', $this->projectId))
                ->where('id', $id)
                ->update(['order' => $index + 1, 'activity_id' => $activityId]);
        }
    }

    // -------------------------------------------------------------------------
    // Activity actions
    // -------------------------------------------------------------------------

    public function createActivityAction(): Action
    {
        return Action::make('createActivity')
            ->label(__('tasks.activities.actions.create'))
            ->icon('heroicon-o-plus')
            ->color('primary')
            ->modalHeading(__('tasks.activities.actions.create'))
            ->schema($this->activityFormSchema())
            ->action(function (array $data): void {
                $project = Project::findOrFail($this->projectId);
                $project->activities()->create([
                    'organization_id' => $project->organization_id,
                    ...$data,
                ]);
                Notification::make()
                    ->title(__('tasks.activities.notifications.created'))
                    ->success()
                    ->send();
            });
    }

    public function editActivityAction(): Action
    {
        return Action::make('editActivity')
            ->iconButton()
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->tooltip(__('tasks.activities.actions.edit'))
            ->modalHeading(__('tasks.activities.actions.edit'))
            ->schema($this->activityFormSchema())
            ->fillForm(function (array $arguments): array {
                $a = Activity::findOrFail($arguments['activityId']);

                return [
                    'name' => $a->name,
                    'description' => $a->description,
                    'order' => $a->order,
                    'status' => $a->status->value,
                    'start_date' => $a->start_date?->format('Y-m-d'),
                    'end_date' => $a->end_date?->format('Y-m-d'),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                Activity::findOrFail($arguments['activityId'])->update($data);
                Notification::make()
                    ->title(__('tasks.activities.notifications.updated'))
                    ->success()
                    ->send();
            });
    }

    public function deleteActivityAction(): Action
    {
        return Action::make('deleteActivity')
            ->iconButton()
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->tooltip(__('tasks.activities.actions.delete'))
            ->requiresConfirmation()
            ->modalHeading(__('tasks.activities.actions.delete_confirm'))
            ->action(function (array $arguments): void {
                Activity::findOrFail($arguments['activityId'])->delete();
                Notification::make()
                    ->title(__('tasks.activities.notifications.deleted'))
                    ->danger()
                    ->send();
            });
    }

    // -------------------------------------------------------------------------
    // Task actions
    // -------------------------------------------------------------------------

    public function createTaskAction(): Action
    {
        return Action::make('createTask')
            ->label(__('tasks.actions.create'))
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->size('sm')
            ->modalHeading(function (array $arguments): string {
                if (empty($arguments['activityId'])) {
                    return __('tasks.actions.create');
                }

                return __('tasks.actions.create_for', [
                    'activity' => Activity::findOrFail($arguments['activityId'])->name,
                ]);
            })
            ->schema($this->taskFormSchema())
            ->action(function (array $data, array $arguments): void {
                $activity = Activity::findOrFail($arguments['activityId']);
                $assignees = $data['assignees'] ?? [];
                unset($data['assignees']);

                $task = Task::create([
                    'organization_id' => $activity->organization_id,
                    'activity_id' => $activity->id,
                    'order' => ($activity->tasks()->max('order') ?? 0) + 1,
                    ...$data,
                ]);

                if ($assignees) {
                    $task->assignees()->sync($assignees);
                }

                Notification::make()
                    ->title(__('tasks.notifications.created'))
                    ->success()
                    ->send();
            });
    }

    public function insertTaskAction(): Action
    {
        return Action::make('insertTask')
            ->iconButton()
            ->icon('heroicon-o-plus-circle')
            ->color('gray')
            ->size('sm')
            ->modalHeading(function (array $arguments): string {
                return ($arguments['position'] ?? 'after') === 'after'
                    ? __('tasks.actions.insert_after')
                    : __('tasks.actions.insert_before');
            })
            ->schema($this->taskFormSchema())
            ->action(function (array $data, array $arguments): void {
                $refTask = Task::findOrFail($arguments['taskId']);
                $position = $arguments['position'] ?? 'after';
                $newOrder = $position === 'after'
                    ? $refTask->order + 1
                    : $refTask->order;

                // Shift subsequent tasks to make room
                Task::where('activity_id', $refTask->activity_id)
                    ->where('order', '>=', $newOrder)
                    ->increment('order');

                $assignees = $data['assignees'] ?? [];
                unset($data['assignees']);

                $task = Task::create([
                    'organization_id' => $refTask->organization_id,
                    'activity_id' => $refTask->activity_id,
                    'order' => $newOrder,
                    ...$data,
                ]);

                if ($assignees) {
                    $task->assignees()->sync($assignees);
                }

                Notification::make()
                    ->title(__('tasks.notifications.created'))
                    ->success()
                    ->send();
            });
    }

    public function scheduleDatesFromPreviousAction(): Action
    {
        return Action::make('scheduleDatesFromPrevious')
            ->iconButton()
            ->icon('heroicon-o-calendar-days')
            ->color('info')
            ->size('sm')
            ->tooltip(__('tasks.actions.schedule_from_previous'))
            ->modalHeading(__('tasks.actions.schedule_from_previous'))
            ->schema(function (array $arguments): array {
                $task = Task::findOrFail($arguments['taskId']);
                $prev = Task::where('activity_id', $task->activity_id)
                    ->where('order', '<', $task->order)
                    ->whereNotNull('due_date')
                    ->orderByDesc('order')
                    ->first();

                $prevInfo = $prev
                    ? __('tasks.actions.previous_task_info', [
                        'name' => $prev->name,
                        'date' => $prev->due_date->format('d/m/Y'),
                    ])
                    : __('tasks.actions.no_previous_with_date');

                return [
                    Placeholder::make('prev_info')
                        ->label(__('tasks.actions.previous_task_label'))
                        ->content($prevInfo)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        DatePicker::make('start_date')
                            ->label(__('tasks.fields.start_date'))
                            ->displayFormat('d/m/Y')
                            ->required(),

                        DatePicker::make('due_date')
                            ->label(__('tasks.fields.due_date'))
                            ->displayFormat('d/m/Y'),
                    ]),
                ];
            })
            ->fillForm(function (array $arguments): array {
                $task = Task::findOrFail($arguments['taskId']);
                $prev = Task::where('activity_id', $task->activity_id)
                    ->where('order', '<', $task->order)
                    ->whereNotNull('due_date')
                    ->orderByDesc('order')
                    ->first();

                $suggestedStart = $prev?->due_date?->addDay();

                return [
                    'start_date' => $suggestedStart?->format('Y-m-d') ?? $task->start_date?->format('Y-m-d'),
                    'due_date' => $task->due_date?->format('Y-m-d'),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                Task::findOrFail($arguments['taskId'])->update([
                    'start_date' => $data['start_date'],
                    'due_date' => $data['due_date'] ?? null,
                ]);
                Notification::make()
                    ->title(__('tasks.notifications.dates_updated'))
                    ->success()
                    ->send();
            });
    }

    public function editTaskAction(): Action
    {
        return Action::make('editTask')
            ->iconButton()
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->tooltip(__('tasks.actions.edit'))
            ->modalHeading(__('tasks.actions.edit'))
            ->schema($this->taskFormSchema())
            ->fillForm(function (array $arguments): array {
                $task = Task::with('assignees')->findOrFail($arguments['taskId']);

                return [
                    'name' => $task->name,
                    'description' => $task->description,
                    'status' => $task->status->value,
                    'priority' => $task->priority->value,
                    'start_date' => $task->start_date?->format('Y-m-d'),
                    'due_date' => $task->due_date?->format('Y-m-d'),
                    'estimated_hours' => $task->estimated_hours,
                    'assignees' => $task->assignees->pluck('id')->toArray(),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $task = Task::findOrFail($arguments['taskId']);
                $assignees = $data['assignees'] ?? [];
                unset($data['assignees']);
                $task->update($data);
                $task->assignees()->sync($assignees);
                Notification::make()
                    ->title(__('tasks.notifications.updated'))
                    ->success()
                    ->send();
            });
    }

    public function deleteTaskAction(): Action
    {
        return Action::make('deleteTask')
            ->iconButton()
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->tooltip(__('tasks.actions.delete'))
            ->requiresConfirmation()
            ->action(function (array $arguments): void {
                Task::findOrFail($arguments['taskId'])->delete();
                Notification::make()
                    ->title(__('tasks.notifications.deleted'))
                    ->danger()
                    ->send();
            });
    }

    // -------------------------------------------------------------------------
    // Form schemas
    // -------------------------------------------------------------------------

    private function activityFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label(__('tasks.activities.fields.name'))
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Textarea::make('description')
                ->label(__('tasks.activities.fields.description'))
                ->rows(2)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                TextInput::make('order')
                    ->label(__('tasks.activities.fields.order'))
                    ->numeric()
                    ->default(fn () => (Project::findOrFail($this->projectId)->activities()->max('order') ?? 0) + 1)
                    ->required(),
            ]),

            Grid::make(2)->schema([
                DatePicker::make('start_date')
                    ->label(__('tasks.activities.fields.start_date'))
                    ->displayFormat('d/m/Y'),

                DatePicker::make('end_date')
                    ->label(__('tasks.activities.fields.end_date'))
                    ->displayFormat('d/m/Y'),
            ]),
        ];
    }

    private function taskFormSchema(): array
    {
        $project = Project::findOrFail($this->projectId);

        return [
            TextInput::make('name')
                ->label(__('tasks.fields.name'))
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Textarea::make('description')
                ->label(__('tasks.fields.description'))
                ->rows(2)
                ->columnSpanFull(),

            Grid::make(2)->schema([
                Select::make('status')
                    ->label(__('tasks.fields.status'))
                    ->options(TaskStatus::class)
                    ->default(TaskStatus::Pendiente)
                    ->required(),

                Select::make('priority')
                    ->label(__('tasks.fields.priority'))
                    ->options(TaskPriority::class)
                    ->default(TaskPriority::Media)
                    ->required(),
            ]),

            Grid::make(2)->schema([
                DatePicker::make('start_date')
                    ->label(__('tasks.fields.start_date'))
                    ->displayFormat('d/m/Y'),

                DatePicker::make('due_date')
                    ->label(__('tasks.fields.due_date'))
                    ->displayFormat('d/m/Y'),
            ]),

            TextInput::make('estimated_hours')
                ->label(__('tasks.fields.estimated_hours'))
                ->numeric()
                ->suffix('h')
                ->minValue(0),

            Select::make('assignees')
                ->label(__('tasks.fields.assignees'))
                ->multiple()
                ->options(
                    User::withoutGlobalScopes()
                        ->where('organization_id', $project->organization_id)
                        ->where('is_active', true)
                        ->pluck('name', 'id')
                )
                ->searchable()
                ->preload()
                ->columnSpanFull(),
        ];
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        $activities = Activity::where('project_id', $this->projectId)
            ->orderBy('order')
            ->with(['tasks' => fn ($q) => $q->with('assignees')->reorder()->orderBy('order')->orderBy('created_at')])
            ->get();

        return view('livewire.activity-accordion', [
            'activities' => $activities,
        ]);
    }
}
