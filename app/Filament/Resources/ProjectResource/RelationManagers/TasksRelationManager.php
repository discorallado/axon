<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('tasks.plural');
    }

    public function form(Schema $schema): Schema
    {
        $project = $this->getOwnerRecord();

        return $schema->schema([
            Select::make('activity_id')
                ->label(__('tasks.fields.activity'))
                ->options($project->activities()->orderBy('order')->pluck('name', 'id'))
                ->searchable()
                ->required()
                ->columnSpanFull(),

            TextInput::make('name')
                ->label(__('tasks.fields.name'))
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Textarea::make('description')
                ->label(__('tasks.fields.description'))
                ->rows(2)
                ->columnSpanFull(),

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

            DatePicker::make('start_date')
                ->label(__('tasks.fields.start_date'))
                ->displayFormat('d/m/Y'),

            DatePicker::make('due_date')
                ->label(__('tasks.fields.due_date'))
                ->displayFormat('d/m/Y'),

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
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $assignees = $data['assignees'] ?? [];
        unset($data['assignees']);

        $task = Task::create([
            'organization_id' => $this->getOwnerRecord()->organization_id,
            ...$data,
        ]);

        if ($assignees) {
            $task->assignees()->sync($assignees);
        }

        return $task;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $assignees = $data['assignees'] ?? [];
        unset($data['assignees']);

        $record->update($data);

        $record->assignees()->sync($assignees);

        return $record;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('code')
                    ->label(__('tasks.fields.code'))
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->width('120px'),

                TextColumn::make('name')
                    ->label(__('tasks.fields.name'))
                    ->searchable()
                    ->wrap()
                    ->description(fn (Task $record) => $record->activity?->name),

                TextColumn::make('status')
                    ->label(__('tasks.fields.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('priority')
                    ->label(__('tasks.fields.priority'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('assignees.name')
                    ->label(__('tasks.fields.assignees'))
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->placeholder('—'),

                TextColumn::make('due_date')
                    ->label(__('tasks.fields.due_date'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn (Task $record) => $record->isOverdue() ? 'danger' : null),
            ])
            ->filters([
                SelectFilter::make('activity_id')
                    ->label(__('tasks.fields.activity'))
                    ->options(fn () => $this->getOwnerRecord()->activities()->orderBy('order')->pluck('name', 'id'))
                    ->searchable(),

                SelectFilter::make('status')
                    ->label(__('tasks.fields.status'))
                    ->options(TaskStatus::class),

                SelectFilter::make('priority')
                    ->label(__('tasks.fields.priority'))
                    ->options(TaskPriority::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('tasks.actions.create'))
                    ->modalHeading(__('tasks.actions.create')),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, Task $record): array {
                        $data['assignees'] = $record->assignees()->pluck('users.id')->toArray();

                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
