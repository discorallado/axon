<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Enums\ActivityStatus;
use App\Models\Activity;
use App\Models\Task;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
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

            DatePicker::make('end_date')
                ->label(__('tasks.activities.fields.end_date'))
                ->displayFormat('d/m/Y'),
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
                    ->wrap(),

                TextColumn::make('status')
                    ->label(__('tasks.activities.fields.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('tasks_count')
                    ->label('Tareas')
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
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['organization_id'] = $this->getOwnerRecord()->organization_id;

                        return $data;
                    }),
            ])
            ->actions([
                Action::make('manage_tasks')
                    ->label('Tareas')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->slideOver()
                    ->schema(fn (Activity $record): array => [
                        Section::make("Tareas de: {$record->name}")
                            ->schema(
                                $record->tasks->isEmpty()
                                    ? [TextEntry::make('empty')->state('Sin tareas aún.')->hiddenLabel()]
                                    : $record->tasks->map(fn (Task $task) => Section::make('')
                                        ->schema([
                                            TextEntry::make('code_'.$task->id)->state($task->code)->label('Código'),
                                            TextEntry::make('name_'.$task->id)->state($task->name)->label('Nombre'),
                                            TextEntry::make('status_'.$task->id)->state($task->status->getLabel())->badge()->color($task->status->getColor())->label('Estado'),
                                        ])->columns(3)
                                    )->toArray()
                            ),
                    ])
                    ->record(fn (Activity $record) => $record),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('order');
    }
}
