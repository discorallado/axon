<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Enums\ActivityStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('tasks.activities.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label(__('tasks.activities.fields.name'))
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Textarea::make('description')
                ->label(__('tasks.activities.fields.description'))
                ->rows(2)
                ->columnSpanFull(),

            Grid::make(3)->schema([
                TextInput::make('order')
                    ->label(__('tasks.activities.fields.order'))
                    ->numeric()
                    ->default(fn () => ($this->getOwnerRecord()->activities()->max('order') ?? 0) + 1)
                    ->required(),

                Select::make('status')
                    ->label(__('tasks.activities.fields.status'))
                    ->options(ActivityStatus::class)
                    ->default(ActivityStatus::Pendiente)
                    ->required(),

                DatePicker::make('start_date')
                    ->label(__('tasks.activities.fields.start_date'))
                    ->displayFormat('d/m/Y'),
            ]),

            DatePicker::make('end_date')
                ->label(__('tasks.activities.fields.end_date'))
                ->displayFormat('d/m/Y'),
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return $this->getRelationship()->create([
            'organization_id' => $this->getOwnerRecord()->organization_id,
            ...$data,
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->sortable()
                    ->width('50px'),

                TextColumn::make('name')
                    ->label(__('tasks.activities.fields.name'))
                    ->searchable()
                    ->wrap()
                    ->description(fn (Activity $record) => $record->description),

                TextColumn::make('status')
                    ->label(__('tasks.activities.fields.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('tasks_count')
                    ->label(__('tasks.plural'))
                    ->counts('tasks')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('start_date')
                    ->label(__('tasks.activities.fields.start_date'))
                    ->date('d/m/Y')
                    ->placeholder('—'),

                TextColumn::make('end_date')
                    ->label(__('tasks.activities.fields.end_date'))
                    ->date('d/m/Y')
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('tasks.activities.actions.create')),
            ])
            ->recordActions([
                Action::make('create_task')
                    ->label(__('tasks.actions.create'))
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->modalHeading(fn (Activity $record) => __('tasks.actions.create_for', ['activity' => $record->name]))
                    ->schema($this->taskFormSchema(...))
                    ->action(function (array $data, Activity $record): void {
                        $assignees = $data['assignees'] ?? [];
                        unset($data['assignees']);

                        $task = Task::create([
                            'organization_id' => $record->organization_id,
                            'activity_id' => $record->id,
                            ...$data,
                        ]);

                        if ($assignees) {
                            $task->assignees()->sync($assignees);
                        }
                    }),

                Action::make('view_tasks')
                    ->label(fn (Activity $record) => __('tasks.plural').' ('.$record->tasks()->count().')')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->slideOver()
                    ->schema(fn (Activity $record): array => $this->taskListSchema($record))
                    ->record(fn (Activity $record) => $record),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('order');
    }

    private function taskFormSchema(): array
    {
        $project = $this->getOwnerRecord();

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

    private function taskListSchema(Activity $activity): array
    {
        $tasks = $activity->tasks()->with('assignees')->get();

        if ($tasks->isEmpty()) {
            return [
                Section::make($activity->name)
                    ->description(__('tasks.empty'))
                    ->schema([]),
            ];
        }

        return [
            Section::make($activity->name)
                ->description($activity->description)
                ->schema(
                    $tasks->map(fn (Task $task) => Section::make('')
                        ->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('code_'.$task->id)
                                    ->state($task->code)
                                    ->label(__('tasks.fields.code'))
                                    ->weight('bold')
                                    ->copyable(),

                                TextEntry::make('name_'.$task->id)
                                    ->state($task->name)
                                    ->label(__('tasks.fields.name'))
                                    ->columnSpan(2),

                                TextEntry::make('status_'.$task->id)
                                    ->state($task->status->getLabel())
                                    ->badge()
                                    ->color($task->status->getColor())
                                    ->label(__('tasks.fields.status')),
                            ]),

                            Grid::make(3)->schema([
                                TextEntry::make('priority_'.$task->id)
                                    ->state($task->priority->getLabel())
                                    ->badge()
                                    ->color($task->priority->getColor())
                                    ->label(__('tasks.fields.priority')),

                                TextEntry::make('due_'.$task->id)
                                    ->state($task->due_date?->format('d/m/Y') ?? '—')
                                    ->label(__('tasks.fields.due_date'))
                                    ->color($task->isOverdue() ? 'danger' : null),

                                TextEntry::make('assignees_'.$task->id)
                                    ->state($task->assignees->pluck('name')->join(', ') ?: '—')
                                    ->label(__('tasks.fields.assignees')),
                            ]),
                        ])
                        ->compact()
                    )->toArray()
                ),
        ];
    }
}
