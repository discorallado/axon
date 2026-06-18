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
use Filament\Schemas\Components\Fieldset;
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
                // ── Cabecera ──────────────────────────────────────────────
                Section::make('Identificación de la Solicitud')
                    ->icon('heroicon-o-document-text')
                    ->iconColor('primary')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('reference_code')
                                ->label('Código de referencia')
                                ->weight('bold')
                                ->copyable()
                                ->icon('heroicon-o-hashtag'),

                            TextEntry::make('status')
                                ->label('Estado')
                                ->badge()
                                ->icon('heroicon-o-arrow-path'),

                            TextEntry::make('submitted_at')
                                ->label('Fecha de envío')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('Pendiente')
                                ->icon('heroicon-o-clock'),

                            TextEntry::make('assignee.name')
                                ->label('Asignado a')
                                ->placeholder('Sin asignar')
                                ->icon('heroicon-o-user'),
                        ]),
                    ]),

                // ── Datos del proyecto ────────────────────────────────────
                Section::make('Datos del Proyecto')
                    ->icon('heroicon-o-building-office-2')
                    ->iconColor('info')
                    ->schema([
                        Fieldset::make('Proyecto')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('project_name')
                                        ->label('Nombre del proyecto / obra')
                                        ->placeholder('—'),

                                    TextEntry::make('installation_location')
                                        ->label('Ubicación de la instalación')
                                        ->placeholder('—'),

                                    TextEntry::make('desired_delivery_date')
                                        ->label('Fecha de entrega deseada')
                                        ->date('d/m/Y')
                                        ->placeholder('—'),

                                    TextEntry::make('cost_center')
                                        ->label('Centro de costo')
                                        ->placeholder('—'),

                                    TextEntry::make('engineering_by')
                                        ->label('Ingeniería básica')
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'csenergia' => 'CSEnergy se encarga',
                                            'cliente' => 'La entrega el cliente',
                                            'conjunta' => 'Conjunta (CSEnergy + cliente)',
                                            default => $state ?? '—',
                                        })
                                        ->placeholder('—'),
                                ]),
                            ]),

                        Fieldset::make('Contacto')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('submitter_name')
                                        ->label('Nombre del contacto')
                                        ->icon('heroicon-o-user'),

                                    TextEntry::make('submitter_email')
                                        ->label('Correo electrónico')
                                        ->copyable()
                                        ->icon('heroicon-o-envelope'),

                                    TextEntry::make('submitter_phone')
                                        ->label('Teléfono')
                                        ->placeholder('—')
                                        ->icon('heroicon-o-phone'),

                                    TextEntry::make('submitter_company')
                                        ->label('Empresa / cliente')
                                        ->placeholder('—')
                                        ->icon('heroicon-o-building-office'),
                                ]),
                            ]),
                    ]),

                // ── Tableros ──────────────────────────────────────────────
                Section::make('Tableros de la Solicitud')
                    ->icon('heroicon-o-square-3-stack-3d')
                    ->iconColor('warning')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Fieldset::make(fn ($record) => ($record->label ?? 'Tablero').'  ×'.($record->quantity ?? 1))
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextEntry::make('board_type')
                                                ->label('Tipo')
                                                ->formatStateUsing(fn ($state, $record) => $record->boardTypeLabel())
                                                ->placeholder('—'),

                                            TextEntry::make('delivery_type')
                                                ->label('¿Qué se requiere?')
                                                ->formatStateUsing(fn ($state) => match ($state) {
                                                    'tablero' => 'Tablero completo',
                                                    'gabinete' => 'Solo gabinete',
                                                    'reparacion' => 'Reparación / modificación',
                                                    default => $state ?? '—',
                                                })
                                                ->placeholder('—'),

                                            TextEntry::make('is_new_installation')
                                                ->label('Tipo de instalación')
                                                ->formatStateUsing(fn ($state) => match ($state) {
                                                    'nueva' => 'Nueva instalación',
                                                    'reemplazo' => 'Reemplazo',
                                                    'ampliacion' => 'Ampliación',
                                                    default => $state ?? '—',
                                                })
                                                ->placeholder('—'),

                                            TextEntry::make('board_function')
                                                ->label('Función principal')
                                                ->columnSpanFull()
                                                ->placeholder('—'),
                                        ]),

                                        Grid::make(4)->schema([
                                            TextEntry::make('supply_voltage')
                                                ->label('Tensión (V)')
                                                ->placeholder('—'),

                                            TextEntry::make('electrical_system')
                                                ->label('Sistema eléctrico')
                                                ->formatStateUsing(fn ($state) => match ($state) {
                                                    'trifasico' => 'Trifásico',
                                                    'bifasico' => 'Bifásico',
                                                    'monofasico' => 'Monofásico',
                                                    'dc' => 'DC',
                                                    default => $state ?? '—',
                                                })
                                                ->placeholder('—'),

                                            TextEntry::make('estimated_power')
                                                ->label('Potencia')
                                                ->formatStateUsing(fn ($state, $record) => $state ? "{$state} {$record->power_unit}" : '—')
                                                ->placeholder('—'),

                                            TextEntry::make('nominal_current')
                                                ->label('Corriente nominal (A)')
                                                ->placeholder('—'),
                                        ]),

                                        Grid::make(4)->schema([
                                            TextEntry::make('location_type')
                                                ->label('Ubicación')
                                                ->formatStateUsing(fn ($state) => match ($state) {
                                                    'interior' => 'Interior',
                                                    'exterior' => 'Exterior',
                                                    default => $state ?? '—',
                                                })
                                                ->badge()
                                                ->color(fn ($state) => $state === 'exterior' ? 'warning' : 'info')
                                                ->placeholder('—'),

                                            TextEntry::make('ip_rating')
                                                ->label('IP')
                                                ->placeholder('—'),

                                            TextEntry::make('ik_rating')
                                                ->label('IK')
                                                ->placeholder('—'),

                                            TextEntry::make('mounting_type')
                                                ->label('Montaje')
                                                ->formatStateUsing(fn ($state) => match ($state) {
                                                    'autosoportado' => 'Autosoportado',
                                                    'mural' => 'Mural / pared',
                                                    'rack_19' => 'Rack 19"',
                                                    'pedestal' => 'Pedestal',
                                                    'otro' => 'Otro',
                                                    default => $state ?? '—',
                                                })
                                                ->placeholder('—'),
                                        ]),

                                        Grid::make(2)->schema([
                                            TextEntry::make('cabinet_material')
                                                ->label('Material del gabinete')
                                                ->placeholder('—'),

                                            TextEntry::make('ventilation_type')
                                                ->label('Ventilación')
                                                ->formatStateUsing(fn ($state) => match ($state) {
                                                    'natural' => 'Natural (rejillas)',
                                                    'forzada' => 'Forzada (ventiladores)',
                                                    'sellado' => 'Sellado',
                                                    'climatizado' => 'Climatizado',
                                                    default => $state ?? '—',
                                                })
                                                ->placeholder('—'),
                                        ]),

                                        TextEntry::make('additional_observations')
                                            ->label('Observaciones del tablero')
                                            ->columnSpanFull()
                                            ->placeholder('—'),
                                    ]),
                            ])
                            ->contained(false),
                    ]),

                // ── Notas internas ────────────────────────────────────────
                Section::make('Notas Internas')
                    ->icon('heroicon-o-pencil-square')
                    ->hidden(fn () => blank($this->record->internal_notes))
                    ->schema([
                        TextEntry::make('internal_notes')
                            ->label('')
                            ->columnSpanFull(),
                    ]),

                // ── Historial de estados ──────────────────────────────────
                Section::make('Historial de Estados')
                    ->icon('heroicon-o-clock')
                    ->iconColor('gray')
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
                                    ->size('sm')
                                    ->placeholder('Sistema'),

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

                // ── Comentarios internos ──────────────────────────────────
                Section::make('Comentarios')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        RepeatableEntry::make('comments')
                            ->label('')
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
                ->label('Cambiar estado')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
                    Select::make('status')
                        ->label('Estado')
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
                ->label('Agregar comentario')
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
