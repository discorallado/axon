<?php

namespace App\Filament\Resources\SubmissionRequestResource\Pages;

use App\Enums\SubmissionStatus;
use App\Filament\Resources\SubmissionRequestResource;
use App\Models\Attachment;
use App\Models\SubmissionRequest;
use App\Models\User;
use App\Services\SubmissionStateMachine;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Parallax\FilamentComments\Infolists\Components\CommentsEntry;
use Parallax\FilamentComments\Models\FilamentComment;

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
                                ->placeholder('Sin registro.')
                                ->icon('heroicon-o-clock'),

                            TextEntry::make('assignee.name')
                                ->label('Asignado a')
                                ->placeholder('Sin registro.')
                                ->icon('heroicon-o-user'),
                        ]),
                    ]),

                // ── Contacto y Proyecto ───────────────────────────────────
                Section::make('Contacto y Proyecto')
                    ->icon('heroicon-o-building-office-2')
                    ->iconColor('info')
                    ->schema([
                        Fieldset::make('Datos del Proyecto')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('project_name')
                                        ->label('Nombre del proyecto / obra')
                                        ->placeholder('Sin registro.'),

                                    TextEntry::make('installation_location')
                                        ->label('Ubicación de la instalación')
                                        ->placeholder('Sin registro.'),

                                    TextEntry::make('desired_delivery_date')
                                        ->label('Fecha de entrega deseada')
                                        ->date('d/m/Y')
                                        ->placeholder('Sin registro.'),

                                    TextEntry::make('cost_center')
                                        ->label('Centro de costo')
                                        ->placeholder('Sin registro.'),

                                    TextEntry::make('engineering_by')
                                        ->label('Ingeniería básica')
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'csenergy' => 'CSEnergy se encarga',
                                            'cliente' => 'La entrega el cliente',
                                            'conjunta' => 'Conjunta (CSEnergy + cliente)',
                                            default => $state ?? 'Sin registro.',
                                        })
                                        ->placeholder('Sin registro.'),
                                ]),
                            ]),

                        Fieldset::make('Datos de Contacto')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('submitter_name')
                                        ->label('Nombre del contacto')
                                        ->placeholder('Sin registro.')
                                        ->icon('heroicon-o-user'),

                                    TextEntry::make('submitter_email')
                                        ->label('Correo electrónico')
                                        ->copyable()
                                        ->placeholder('Sin registro.')
                                        ->icon('heroicon-o-envelope'),

                                    TextEntry::make('submitter_phone')
                                        ->label('Teléfono')
                                        ->placeholder('Sin registro.')
                                        ->icon('heroicon-o-phone'),

                                    TextEntry::make('submitter_company')
                                        ->label('Empresa / cliente')
                                        ->placeholder('Sin registro.')
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
                                        // Identificación
                                        Grid::make(3)->schema([
                                            TextEntry::make('board_type')
                                                ->label('Tipo de tablero')
                                                ->formatStateUsing(fn ($state, $record) => $record->boardTypeLabel())
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('delivery_type')
                                                ->label('¿Qué se requiere?')
                                                ->formatStateUsing(fn ($state) => match ($state) {
                                                    'tablero' => 'Tablero completo',
                                                    'gabinete' => 'Solo gabinete',
                                                    'reparacion' => 'Reparación / modificación',
                                                    default => $state ?? 'Sin registro.',
                                                })
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('is_new_installation')
                                                ->label('Tipo de instalación')
                                                ->formatStateUsing(fn ($state) => match ($state) {
                                                    'nueva' => 'Nueva instalación',
                                                    'reemplazo' => 'Reemplazo',
                                                    'ampliacion' => 'Ampliación',
                                                    default => $state ?? 'Sin registro.',
                                                })
                                                ->placeholder('Sin registro.'),
                                        ]),

                                        TextEntry::make('board_function')
                                            ->label('Función principal')
                                            ->columnSpanFull()
                                            ->placeholder('Sin registro.'),

                                        Grid::make(2)->schema([
                                            TextEntry::make('loads_to_feed')
                                                ->label('Cargas a alimentar')
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('number_of_circuits')
                                                ->label('N.° de salidas / circuitos')
                                                ->placeholder('Sin registro.'),
                                        ]),

                                        // Instalación
                                        Grid::make(4)->schema([
                                            TextEntry::make('location_type')
                                                ->label('Ubicación')
                                                ->formatStateUsing(fn ($state) => match ($state) {
                                                    'interior' => 'Interior',
                                                    'exterior' => 'Exterior',
                                                    default => $state ?? 'Sin registro.',
                                                })
                                                ->badge()
                                                ->color(fn ($state) => $state === 'exterior' ? 'warning' : 'info')
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('special_environment')
                                                ->label('Ambiente especial')
                                                ->listWithLineBreaks()
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('ip_rating')
                                                ->label('IP')
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('ik_rating')
                                                ->label('IK')
                                                ->placeholder('Sin registro.'),
                                        ]),

                                        Grid::make(3)->schema([
                                            TextEntry::make('mounting_type')
                                                ->label('Montaje')
                                                ->formatStateUsing(fn ($state) => match ($state) {
                                                    'autosoportado' => 'Autosoportado',
                                                    'mural' => 'Mural / pared',
                                                    'rack_19' => 'Rack 19"',
                                                    'pedestal' => 'Pedestal',
                                                    'otro' => 'Otro',
                                                    default => $state ?? 'Sin registro.',
                                                })
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('max_height')
                                                ->label('Alto máx. (mm)')
                                                ->placeholder('Sin registro.')
                                                ->visible(fn ($record) => $record->has_dimension_restrictions),

                                            TextEntry::make('max_width')
                                                ->label('Ancho máx. (mm)')
                                                ->placeholder('Sin registro.')
                                                ->visible(fn ($record) => $record->has_dimension_restrictions),

                                            TextEntry::make('max_depth')
                                                ->label('Prof. máx. (mm)')
                                                ->placeholder('Sin registro.')
                                                ->visible(fn ($record) => $record->has_dimension_restrictions),
                                        ]),

                                        TextEntry::make('additional_installation_conditions')
                                            ->label('Condiciones adicionales de instalación')
                                            ->placeholder('Sin registro.')
                                            ->columnSpanFull(),

                                        // Especificaciones Eléctricas
                                        Grid::make(4)->schema([
                                            TextEntry::make('supply_voltage')
                                                ->label('Tensión (V)')
                                                ->formatStateUsing(fn ($state, $record) => $state === 'otro'
                                                    ? ($record->supply_voltage_other.' V')
                                                    : ($state ? $state.' V' : 'Sin registro.'))
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('electrical_system')
                                                ->label('Sistema eléctrico')
                                                ->formatStateUsing(fn ($state, $record) => match ($state) {
                                                    'trifasico' => 'Trifásico (3F+N)',
                                                    'bifasico' => 'Bifásico (2F)',
                                                    'monofasico' => 'Monofásico (1F+N)',
                                                    'dc' => 'Corriente continua (DC)',
                                                    'otro' => $record->electrical_system_other ?? 'Otro',
                                                    default => $state ?? 'Sin registro.',
                                                })
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('estimated_power')
                                                ->label('Potencia')
                                                ->formatStateUsing(fn ($state, $record) => $state
                                                    ? "{$state} {$record->power_unit}"
                                                    : 'Sin registro.')
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('nominal_current')
                                                ->label('Corriente nominal (A)')
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('frequency')
                                                ->label('Frecuencia')
                                                ->formatStateUsing(fn ($state, $record) => $state === 'otro'
                                                    ? ($record->other_frequency.' Hz')
                                                    : ($state ? $state.' Hz' : 'Sin registro.'))
                                                ->placeholder('Sin registro.'),
                                        ]),

                                        TextEntry::make('required_protections')
                                            ->label('Protecciones requeridas')
                                            ->listWithLineBreaks()
                                            ->placeholder('Sin registro.')
                                            ->columnSpanFull(),

                                        TextEntry::make('preferred_brands')
                                            ->label('Marcas preferidas')
                                            ->listWithLineBreaks()
                                            ->placeholder('Sin registro.')
                                            ->columnSpanFull(),

                                        // Diseño Constructivo
                                        Grid::make(3)->schema([
                                            TextEntry::make('cabinet_material')
                                                ->label('Material del gabinete')
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('special_color')
                                                ->label('Color del gabinete')
                                                ->formatStateUsing(fn ($state) => match ($state) {
                                                    '7035' => 'RAL 7035 — Gris claro',
                                                    '7016' => 'RAL 7016 — Gris antracita',
                                                    '9016' => 'RAL 9016 — Blanco tráfico',
                                                    '9005' => 'RAL 9005 — Negro intenso',
                                                    '5010' => 'RAL 5010 — Azul genciana',
                                                    '6005' => 'RAL 6005 — Verde musgo',
                                                    'otro' => 'Otro',
                                                    default => $state ?? 'Sin registro.',
                                                })
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('ventilation_type')
                                                ->label('Ventilación')
                                                ->formatStateUsing(fn ($state) => match ($state) {
                                                    'natural' => 'Natural (rejillas)',
                                                    'forzada' => 'Forzada (ventiladores)',
                                                    'sellado' => 'Sellado',
                                                    'climatizado' => 'Climatizado',
                                                    default => $state ?? 'Sin registro.',
                                                })
                                                ->placeholder('Sin registro.'),

                                            TextEntry::make('future_expansion')
                                                ->label('Expansión futura')
                                                ->formatStateUsing(fn ($state) => match ($state) {
                                                    'no' => 'Sin espacio adicional',
                                                    '10' => '~10% espacio libre',
                                                    '20' => '~20% espacio libre',
                                                    '30' => '~30% espacio libre',
                                                    'otro' => 'Otro porcentaje',
                                                    default => $state ?? 'Sin registro.',
                                                })
                                                ->placeholder('Sin registro.'),
                                        ]),

                                        // Adjuntos del ítem
                                        TextEntry::make('id')
                                            ->label('Adjuntos del tablero')
                                            ->columnSpanFull()
                                            ->formatStateUsing(function ($state, $record) {
                                                $attachments = Attachment::withoutGlobalScopes()
                                                    ->where('attachable_type', 'submission_item')
                                                    ->where('attachable_id', $record->id)
                                                    ->get();

                                                if ($attachments->isEmpty()) {
                                                    return 'Sin registro.';
                                                }

                                                $labels = [
                                                    'load_list' => 'Lista de cargas',
                                                    'unilineal_diagram' => 'Diagrama unilineal',
                                                    'mechanical_plans' => 'Planos mecánicos',
                                                ];

                                                return $attachments->map(function ($a) use ($labels) {
                                                    $label = $labels[$a->tag] ?? $a->tag;

                                                    return "{$label}: {$a->original_name}";
                                                })->join("\n");
                                            })
                                            ->html(false),

                                        TextEntry::make('additional_observations')
                                            ->label('Observaciones del tablero')
                                            ->columnSpanFull()
                                            ->placeholder('Sin registro.'),
                                    ]),
                            ])
                            ->contained(false),
                    ]),

                // ── Documentación del Proyecto ────────────────────────────
                Section::make('Documentación del Proyecto')
                    ->icon('heroicon-o-paper-clip')
                    ->iconColor('success')
                    ->headerActions([
                        Action::make('delete_attachments')
                            ->label('Eliminar adjuntos')
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->requiresConfirmation(false)
                            ->form(function (): array {
                                $tagLabels = [
                                    'technical_specs' => 'Especificaciones técnicas',
                                    'site_photo' => 'Fotografía del sitio',
                                    'load_list' => 'Lista de cargas',
                                    'unilineal_diagram' => 'Diagrama unilineal',
                                    'mechanical_plans' => 'Planos mecánicos',
                                ];

                                $options = [];

                                foreach ($this->record->attachments as $att) {
                                    $label = $tagLabels[$att->tag] ?? $att->tag;
                                    $options[$att->id] = "[Solicitud] {$label}: {$att->original_name}";
                                }

                                foreach ($this->record->items()->with('attachments')->get() as $item) {
                                    foreach ($item->attachments as $att) {
                                        $label = $tagLabels[$att->tag] ?? $att->tag;
                                        $itemLabel = $item->label ?? 'Tablero';
                                        $options[$att->id] = "[{$itemLabel}] {$label}: {$att->original_name}";
                                    }
                                }

                                if (empty($options)) {
                                    return [
                                        Placeholder::make('no_attachments')
                                            ->label('')
                                            ->content('Esta solicitud no tiene adjuntos.'),
                                    ];
                                }

                                return [
                                    CheckboxList::make('attachment_ids')
                                        ->label('Selecciona los adjuntos a eliminar')
                                        ->options($options)
                                        ->required()
                                        ->noSearchResultsMessage('No hay adjuntos.')
                                        ->searchable(count($options) > 5),
                                ];
                            })
                            ->action(function (array $data): void {
                                if (empty($data['attachment_ids'] ?? [])) {
                                    return;
                                }

                                $allowedParentIds = array_merge(
                                    [$this->record->id],
                                    $this->record->items()->withTrashed()->pluck('id')->toArray()
                                );

                                $toDelete = Attachment::withoutGlobalScopes()
                                    ->whereIn('id', $data['attachment_ids'])
                                    ->where('organization_id', $this->record->organization_id)
                                    ->whereIn('attachable_id', $allowedParentIds)
                                    ->get();

                                foreach ($toDelete as $attachment) {
                                    if (Storage::disk($attachment->disk)->exists($attachment->path)) {
                                        Storage::disk($attachment->disk)->delete($attachment->path);
                                    }
                                    $attachment->delete();
                                }

                                Notification::make()
                                    ->title('Adjunto(s) eliminado(s).')
                                    ->success()
                                    ->send();

                                $this->dispatch('$refresh');
                            })
                            ->visible(fn () => auth()->user()->can('deleteAttachment', $this->record)),
                    ])
                    ->schema([
                        TextEntry::make('id')
                            ->label('Especificaciones técnicas')
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state, $record) {
                                $att = Attachment::withoutGlobalScopes()
                                    ->where('attachable_type', 'submission_request')
                                    ->where('attachable_id', $record->id)
                                    ->where('tag', 'technical_specs')
                                    ->first();

                                return $att ? $att->original_name : 'Sin registro.';
                            }),

                        TextEntry::make('id')
                            ->label('Fotografías del sitio')
                            ->key('site_photos_entry')
                            ->columnSpanFull()
                            ->formatStateUsing(function ($state, $record) {
                                $attachments = Attachment::withoutGlobalScopes()
                                    ->where('attachable_type', 'submission_request')
                                    ->where('attachable_id', $record->id)
                                    ->where('tag', 'site_photo')
                                    ->get();

                                if ($attachments->isEmpty()) {
                                    return 'Sin registro.';
                                }

                                return $attachments->map(fn ($a) => $a->original_name)->join("\n");
                            }),

                        TextEntry::make('project_observations')
                            ->label('Observaciones generales del proyecto')
                            ->columnSpanFull()
                            ->placeholder('Sin registro.'),
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
                                    ->placeholder('Sin registro.')
                                    ->badge()
                                    ->size('sm'),

                                TextEntry::make('to_status')
                                    ->label('Hacia')
                                    ->badge()
                                    ->size('sm'),

                                TextEntry::make('comment')
                                    ->label('Comentario')
                                    ->placeholder('Sin registro.')
                                    ->columnSpanFull()
                                    ->size('sm'),
                            ])
                            ->columns(4),
                    ]),

                // ── Comentarios internos (parallax/filament-comments) ─────
                Section::make('Comentarios Internos')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        CommentsEntry::make('filamentComments'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn () => route('submissions.pdf', $this->record))
                ->openUrlInNewTab(),

            Action::make('assign')
                ->label('Asignar responsable')
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->form([
                    Select::make('assigned_to')
                        ->label('Responsable')
                        ->options(fn () => User::where('organization_id', $this->record->organization_id)->pluck('name', 'id'))
                        ->default(fn () => $this->record->assigned_to)
                        ->nullable()
                        ->placeholder('Sin asignar'),
                ])
                ->action(function (array $data): void {
                    $this->record->update(['assigned_to' => $data['assigned_to']]);
                    $this->refreshFormData(['assigned_to']);
                    Notification::make()->title('Responsable actualizado.')->success()->send();
                })
                ->visible(fn () => auth()->user()->can('assign', $this->record)),

            Action::make('change_status')
                ->label('Cambiar estado')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
                    Select::make('status')
                        ->label('Estado')
                        ->options(function () {
                            $machine = app(SubmissionStateMachine::class);

                            return collect($machine->allowedNextStatuses($this->record))
                                ->mapWithKeys(fn (SubmissionStatus $s) => [$s->value => $s->getLabel()])
                                ->all();
                        })
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
                        DB::transaction(function () use ($machine, $toStatus, $data): void {
                            $machine->transition(auth()->user(), $this->record, $toStatus, $data['comment'] ?? null);

                            if (! blank($data['comment'] ?? null)) {
                                FilamentComment::create([
                                    'user_id' => auth()->id(),
                                    'subject_type' => SubmissionRequest::class,
                                    'subject_id' => $this->record->id,
                                    'comment' => '[Cambio de estado → '.($toStatus->getLabel() ?? $toStatus->value).'] '.$data['comment'],
                                ]);
                            }
                        });

                        $this->refreshFormData(['status']);
                        Notification::make()->title('Estado actualizado.')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                })
                ->visible(fn () => auth()->user()->can('updateStatus', $this->record)),

        ];
    }
}
