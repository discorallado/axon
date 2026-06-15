<?php

namespace App\Filament\Resources;

use App\Enums\SubmissionStatus;
use App\Filament\Resources\SubmissionRequestResource\Pages;
use App\Models\SubmissionRequest;
use App\Models\User;
use App\Services\SubmissionStateMachine;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubmissionRequestResource extends Resource
{
    protected static ?string $model = SubmissionRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected static string|\UnitEnum|null $navigationGroup = 'Solicitudes';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('submissions.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('submissions.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_code')
                    ->label(__('submissions.fields.reference_code'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label(__('submissions.fields.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('submitter_name')
                    ->label(__('submissions.fields.submitter_name'))
                    ->searchable(),

                TextColumn::make('submitter_company')
                    ->label(__('submissions.fields.submitter_company'))
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('assignee.name')
                    ->label(__('submissions.fields.assigned_to'))
                    ->placeholder('Sin asignar')
                    ->toggleable(),

                TextColumn::make('submitted_at')
                    ->label(__('submissions.fields.submitted_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('submissions.fields.status'))
                    ->options(SubmissionStatus::class),

                SelectFilter::make('assigned_to')
                    ->label(__('submissions.fields.assigned_to'))
                    ->relationship('assignee', 'name'),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('change_status')
                    ->label(__('submissions.actions.change_status'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Select::make('status')
                            ->label(__('submissions.fields.status'))
                            ->options(SubmissionStatus::class)
                            ->required(),

                        Textarea::make('comment')
                            ->label('Comentario (opcional)')
                            ->rows(2),
                    ])
                    ->action(function (SubmissionRequest $record, array $data): void {
                        $toStatus = $data['status'] instanceof SubmissionStatus
                            ? $data['status']
                            : SubmissionStatus::from($data['status']);
                        $machine = app(SubmissionStateMachine::class);

                        try {
                            $machine->transition(auth()->user(), $record, $toStatus, $data['comment'] ?? null);
                            Notification::make()->title('Estado actualizado.')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    })
                    ->visible(fn (SubmissionRequest $record) => auth()->user()->can('updateStatus', $record)),

                Action::make('assign')
                    ->label(__('submissions.actions.assign'))
                    ->icon('heroicon-o-user')
                    ->color('gray')
                    ->form([
                        Select::make('assigned_to')
                            ->label(__('submissions.fields.assigned_to'))
                            ->options(fn (SubmissionRequest $record) => User::where('organization_id', $record->organization_id)
                                ->pluck('name', 'id')
                            )
                            ->nullable()
                            ->placeholder('Sin asignar'),
                    ])
                    ->action(function (SubmissionRequest $record, array $data): void {
                        $record->update(['assigned_to' => $data['assigned_to']]);
                        Notification::make()->title('Responsable actualizado.')->success()->send();
                    })
                    ->visible(fn (SubmissionRequest $record) => auth()->user()->can('assign', $record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissionRequests::route('/'),
            'view' => Pages\ViewSubmissionRequest::route('/{record}'),
        ];
    }
}
