<?php

namespace App\Filament\Resources\SubmissionRequestResource\Pages;

use App\Enums\SubmissionStatus;
use App\Filament\Resources\SubmissionRequestResource;
use App\Models\Comment;
use App\Models\SubmissionRequest;
use App\Services\SubmissionStateMachine;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewSubmissionRequest extends ViewRecord
{
    protected static string $resource = SubmissionRequestResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('Datos de la solicitud')
                    ->columns()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('reference_code')
                                ->label(__('submissions.fields.reference_code'))
                                ->weight('bold')
                                ->copyable(),

                            TextEntry::make('status')
                                ->label(__('submissions.fields.status'))
                                ->badge(),

                            TextEntry::make('submitted_at')
                                ->label(__('submissions.fields.submitted_at'))
                                ->dateTime('d/m/Y H:i'),

                            TextEntry::make('submitter_name')
                                ->label(__('submissions.fields.submitter_name')),

                            TextEntry::make('submitter_email')
                                ->label(__('submissions.fields.submitter_email'))
                                ->copyable(),

                            TextEntry::make('submitter_phone')
                                ->label(__('submissions.fields.submitter_phone'))
                                ->placeholder('—'),

                            TextEntry::make('submitter_company')
                                ->label(__('submissions.fields.submitter_company'))
                                ->placeholder('—'),

                            TextEntry::make('assignee.name')
                                ->label(__('submissions.fields.assigned_to'))
                                ->placeholder('Sin asignar'),
                        ]),
                    ]),

                Section::make(__('submissions.fields.answers'))
                    ->schema([
                        RepeatableEntry::make('answers')
                            ->label('')
                            ->schema([
                                TextEntry::make('question_label')
                                    ->label('Pregunta')
                                    ->weight('medium'),

                                TextEntry::make('display_value')
                                    ->label('Respuesta')
                                    ->getStateUsing(fn ($record) => $record->displayValue())
                                    ->placeholder('(sin respuesta)'),
                            ])
                            ->columns(2),
                    ]),

                Section::make('Adjuntos')
                    ->hidden(fn () => $this->record->attachments->isEmpty())
                    ->schema([
                        RepeatableEntry::make('attachments')
                            ->label('')
                            ->schema([
                                TextEntry::make('original_name')
                                    ->label('Archivo')
                                    ->icon('heroicon-o-paper-clip')
                                    ->url(fn ($record) => route('attachments.download', $record))
                                    ->openUrlInNewTab(),

                                TextEntry::make('mime_type')
                                    ->label('Tipo')
                                    ->placeholder('—')
                                    ->size('sm'),

                                TextEntry::make('size_bytes')
                                    ->label('Tamaño')
                                    ->getStateUsing(fn ($record) => $record->humanSize())
                                    ->size('sm'),

                                TextEntry::make('created_at')
                                    ->label('Subido')
                                    ->dateTime('d/m/Y H:i')
                                    ->size('sm'),
                            ])
                            ->columns(4),
                    ]),

                Section::make(__('submissions.fields.history'))
                    ->schema([
                        RepeatableEntry::make('statusHistories')
                            ->label('')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Fecha')
                                    ->dateTime('d/m/Y H:i')
                                    ->size('sm'),

                                TextEntry::make('changedBy.name')
                                    ->label('Usuario')
                                    ->size('sm'),

                                TextEntry::make('from_status')
                                    ->label('Desde')
                                    ->placeholder('—')
                                    ->badge()
                                    ->size('sm'),

                                TextEntry::make('to_status')
                                    ->label('Hacia')
                                    ->badge()
                                    ->size('sm'),

                                TextEntry::make('comment')
                                    ->label('Comentario')
                                    ->placeholder('—')
                                    ->columnSpanFull()
                                    ->size('sm'),
                            ])
                            ->columns(4),
                    ]),

                Section::make(__('submissions.fields.comments'))
                    ->schema([
                        RepeatableEntry::make('comments')
                            ->label('Comentarios')
                            ->schema([
                                TextEntry::make('author.name')
                                    ->label('Usuario')
                                    ->weight('medium')
                                    ->size('sm'),
                                TextEntry::make('created_at')
                                    ->label('Fecha')
                                    ->since()
                                    ->size('sm'),
                                TextEntry::make('body')
                                    ->label('Comentario')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
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
                ->action(function (array $data): void {
                    $toStatus = $data['status'] instanceof SubmissionStatus
                        ? $data['status']
                        : SubmissionStatus::from($data['status']);
                    $machine = app(SubmissionStateMachine::class);

                    try {
                        $machine->transition(auth()->user(), $this->record, $toStatus, $data['comment'] ?? null);
                        $this->refreshFormData(['status']);
                        Notification::make()->title('Estado actualizado.')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                })
                ->visible(fn () => auth()->user()->can('updateStatus', $this->record)),

            Action::make('add_comment')
                ->label(__('submissions.actions.add_comment'))
                ->icon('heroicon-o-chat-bubble-left')
                ->color('gray')
                ->form([
                    Textarea::make('body')
                        ->label('Comentario')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    Comment::create([
                        'organization_id' => $this->record->organization_id,
                        'commentable_type' => SubmissionRequest::class,
                        'commentable_id' => $this->record->id,
                        'user_id' => auth()->id(),
                        'body' => $data['body'],
                    ]);
                    Notification::make()->title('Comentario agregado.')->success()->send();
                })
                ->visible(fn () => auth()->user()->can('comment', $this->record)),
        ];
    }
}
