<?php

namespace App\Livewire;

use App\Models\Organization;
use App\Models\SubmissionItem;
use App\Models\SubmissionRequest;
use App\Models\User;
use App\Notifications\NewSubmissionReceived;
use App\Notifications\SubmissionConfirmed;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component;

class PublicFormWizard extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public array $data = [];

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public bool $submitted = false;

    public ?string $referenceCode = null;

    public ?int $editingItemIndex = null;

    public ?string $editingSubmissionId = null;

    public function mount(?string $submission = null): void
    {
        if ($submission) {
            $record = SubmissionRequest::withoutGlobalScopes()->findOrFail($submission);
            $this->editingSubmissionId = $record->id;
            $this->form->fill([
                'power_unit' => 'kW',
                'project_name' => $record->project_name,
                'installation_location' => $record->installation_location,
                'cost_center' => $record->cost_center,
                'desired_delivery_date' => $record->desired_delivery_date?->format('Y-m-d'),
                'engineering_by' => $record->engineering_by,
                'contact_name' => $record->submitter_name,
                'contact_email' => $record->submitter_email,
                'contact_phone' => $record->submitter_phone,
                'client_company' => $record->submitter_company,
            ]);
            $this->items = $record->items->map(fn (SubmissionItem $item) => [
                'label' => $item->label,
                'quantity' => $item->quantity,
                'sort_order' => $item->sort_order,
                'delivery_type' => $item->delivery_type,
                'is_new_installation' => $item->is_new_installation,
                'board_type' => $item->board_type,
                'other_board_type' => $item->other_board_type,
                'board_function' => $item->board_function,
                'loads_to_feed' => $item->loads_to_feed,
                'number_of_circuits' => $item->number_of_circuits,
                'location_type' => $item->location_type,
                'special_environment' => $item->special_environment,
                'other_special_environment' => $item->other_special_environment,
                'ip_rating' => $item->ip_rating,
                'ik_rating' => $item->ik_rating,
                'mounting_type' => $item->mounting_type,
                'has_dimension_restrictions' => $item->has_dimension_restrictions,
                'max_height' => $item->max_height,
                'max_width' => $item->max_width,
                'max_depth' => $item->max_depth,
                'additional_installation_conditions' => $item->additional_installation_conditions,
                'supply_voltage' => $item->supply_voltage,
                'supply_voltage_other' => $item->supply_voltage_other,
                'electrical_system' => $item->electrical_system,
                'electrical_system_other' => $item->electrical_system_other,
                'estimated_power' => $item->estimated_power,
                'power_unit' => $item->power_unit,
                'nominal_current' => $item->nominal_current,
                'frequency' => $item->frequency,
                'other_frequency' => $item->other_frequency,
                'required_protections' => $item->required_protections,
                'preferred_brands' => $item->preferred_brands,
                'cabinet_material' => $item->cabinet_material,
                'special_color' => $item->special_color,
                'ventilation_type' => $item->ventilation_type,
                'future_expansion' => $item->future_expansion,
                'additional_observations' => $item->additional_observations,
            ])->toArray();
        } else {
            $this->form->fill([
                'power_unit' => 'kW',
            ]);
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Wizard::make($this->steps())
                    ->skippable(false)
                    ->submitAction(view('livewire.partials.wizard-submit-btn'))
                    ->persistStepInQueryString(),
            ])
            ->statePath('data');
    }

    protected function steps(): array
    {
        return [
            $this->contactStep(),
            $this->tablerosStep(),
            $this->documentationStep(),
        ];
    }

    // -------------------------------------------------------------------------
    // Step 1 — Contacto y Proyecto
    // -------------------------------------------------------------------------
    protected function contactStep(): Step
    {
        return Step::make('Contacto y Proyecto')
            ->icon('heroicon-o-user')
            ->schema([
                Fieldset::make('Datos del Proyecto')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('project_name')
                                ->label('Nombre del Proyecto / Obra')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('installation_location')
                                ->label('Ubicación de la Instalación')
                                ->required()
                                ->maxLength(255),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('cost_center')
                                ->label('Centro de Costo Asociado')
                                ->maxLength(100),

                            DatePicker::make('desired_delivery_date')
                                ->label('Fecha de Entrega Deseada')
                                ->displayFormat('d/m/Y')
                                ->native(false),
                        ]),

                        Radio::make('engineering_by')
                            ->label('Ingeniería básica — ¿quién la entrega?')
                            ->required()
                            ->options([
                                'csenergia' => 'CSEnergy se encarga',
                                'cliente' => 'La entrega el cliente',
                                'conjunta' => 'Conjunta (CSEnergy + cliente)',
                            ])
                            ->inline(true),
                    ]),

                Fieldset::make('Datos de Contacto')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('contact_name')
                                ->label('Nombre del Contacto')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('client_company')
                                ->label('Empresa / Cliente')
                                ->required()
                                ->maxLength(255),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('contact_email')
                                ->label('Correo Electrónico')
                                ->email()
                                ->required()
                                ->maxLength(255),

                            TextInput::make('contact_phone')
                                ->label('Teléfono de Contacto')
                                ->tel()
                                ->maxLength(50),
                        ]),
                    ]),
            ]);
    }

    // -------------------------------------------------------------------------
    // Step 2 — Tableros
    // -------------------------------------------------------------------------
    protected function tablerosStep(): Step
    {
        return Step::make('Tableros')
            ->icon('heroicon-o-square-3-stack-3d')
            ->schema([
                Placeholder::make('items_list')
                    ->label('Tableros en esta solicitud')
                    ->content(fn () => $this->renderItemsHtml()),
            ]);
    }

    protected function renderItemsHtml(): HtmlString
    {
        $html = '<div class="space-y-2">';

        foreach ($this->items as $i => $item) {
            $label = e($item['label'] ?? 'Tablero '.($i + 1));
            $qty = (int) ($item['quantity'] ?? 1);
            $type = e($item['board_type'] ?? '');

            $typeLabels = [
                'fuerza' => 'Fuerza / Potencia',
                'alumbrado' => 'Alumbrado / Distribución',
                'control' => 'Control / Automatización',
                'transfer' => 'Transferencia (ATS/MTS)',
                'sincronizacion' => 'Sincronización Generadores',
                'remoto' => 'Distribución Remoto',
                'pfcs' => 'Factor de Potencia',
                'medicion' => 'Medición',
                'variadores' => 'Variadores de Frecuencia',
                'arrancadores' => 'Arrancadores Suaves',
                'ups' => 'UPS / Respaldo',
                'otro' => e($item['other_board_type'] ?? 'Otro'),
            ];
            $typeLabel = $typeLabels[$type] ?? $type;

            $html .= <<<HTML
            <div class="flex items-center justify-between rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3">
                <div class="min-w-0 flex-1">
                    <span class="font-medium text-zinc-900">{$label}</span>
                    <span class="ml-2 text-xs text-zinc-500">{$typeLabel}</span>
                    <span class="ml-2 inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600">
                        ×{$qty}
                    </span>
                </div>
                <div class="flex shrink-0 items-center gap-3 ml-4">
                    <button type="button"
                        wire:click="editItem({$i})"
                        class="text-sm font-medium text-blue-600 hover:text-blue-800">
                        Editar
                    </button>
                    <button type="button"
                        wire:click="removeItem({$i})"
                        wire:confirm="¿Eliminar este tablero de la solicitud?"
                        class="text-sm font-medium text-red-500 hover:text-red-700">
                        Eliminar
                    </button>
                </div>
            </div>
            HTML;
        }

        if (empty($this->items)) {
            $html .= <<<'HTML'
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 px-6 py-8 text-center">
                <svg class="mx-auto mb-3 h-8 w-8 text-zinc-400" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <p class="text-sm font-medium text-zinc-600">Aún no hay tableros en esta solicitud.</p>
                <p class="mt-1 text-xs text-zinc-400">Haz clic en "Agregar tablero" para añadir el primero.</p>
            </div>
            HTML;
        }

        $html .= <<<'HTML'
        <div class="mt-4 flex justify-end">
            <button type="button"
                wire:click="mountAction('tablero')"
                class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-500 transition-colors"
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Agregar tablero
            </button>
        </div>
        HTML;

        $html .= '</div>';

        return new HtmlString($html);
    }

    // -------------------------------------------------------------------------
    // Step 3 — Documentación y Cierre
    // -------------------------------------------------------------------------
    protected function documentationStep(): Step
    {
        return Step::make('Documentación')
            ->icon('heroicon-o-paper-clip')
            ->schema([
                Fieldset::make('Especificaciones del Proyecto')
                    ->schema([
                        Toggle::make('has_technical_specs')
                            ->label('¿Tiene especificaciones técnicas o pliego de condiciones?')
                            ->live(),

                        FileUpload::make('technical_specs')
                            ->label('Especificaciones técnicas / pliego de condiciones')
                            ->visible(fn ($get) => (bool) $get('has_technical_specs'))
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->maxSize(20480)
                            ->disk('local')
                            ->directory('solicitudes/specs'),
                    ]),

                Fieldset::make('Material Gráfico')
                    ->schema([
                        FileUpload::make('site_photos')
                            ->label('Fotografías del sitio / tablero existente')
                            ->hint('Adjunte fotos del lugar de instalación, espacio disponible u otro material gráfico relevante.')
                            ->multiple()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->maxSize(10240)
                            ->maxFiles(10)
                            ->disk('local')
                            ->directory('solicitudes/fotos'),
                    ]),

                Fieldset::make('Observaciones Finales')
                    ->schema([
                        Textarea::make('project_observations')
                            ->label('Observaciones generales del proyecto')
                            ->rows(4)
                            ->maxLength(2000)
                            ->placeholder('Indique cualquier requerimiento especial no contemplado en los pasos anteriores...'),
                    ]),
            ]);
    }

    // -------------------------------------------------------------------------
    // Acción modal: agregar / editar tablero
    // -------------------------------------------------------------------------
    public function tableroAction(): Action
    {
        return Action::make('tablero')
            ->modalHeading(fn () => $this->editingItemIndex !== null ? 'Editar Tablero' : 'Agregar Tablero')
            ->modalWidth('4xl')
            ->fillForm(function (): array {
                if ($this->editingItemIndex !== null) {
                    return $this->items[$this->editingItemIndex] ?? [];
                }

                return ['quantity' => 1, 'power_unit' => 'kW'];
            })
            ->steps($this->tableroSteps())
            ->action(function (array $data): void {
                if ($this->editingItemIndex !== null) {
                    $this->items[$this->editingItemIndex] = $data;
                    $this->editingItemIndex = null;
                } else {
                    $data['sort_order'] = count($this->items);
                    $this->items[] = $data;
                }

                Notification::make()
                    ->title($this->editingItemIndex !== null ? 'Tablero actualizado.' : 'Tablero agregado.')
                    ->success()
                    ->send();
            })
            ->modalCancelAction(fn (Action $action) => $action->action(function () {
                $this->editingItemIndex = null;
            }));
    }

    protected function tableroSteps(): array
    {
        return [
            Step::make('Identificación')
                ->icon('heroicon-o-tag')
                ->schema([
                    Fieldset::make('Nombre y Tipo')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('label')
                                    ->label('Nombre del tablero')
                                    ->required()
                                    ->maxLength(150)
                                    ->placeholder('Ej.: TG Principal, T-Alumbrado Norte')
                                    ->hint('Nombre que identifica este tablero en la solicitud.'),

                                TextInput::make('quantity')
                                    ->label('Cantidad de unidades')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(999)
                                    ->default(1),
                            ]),

                            Select::make('board_type')
                                ->label('Tipo de tablero')
                                ->required()
                                ->options([
                                    'fuerza' => 'Tablero de Fuerza / Potencia',
                                    'alumbrado' => 'Tablero de Alumbrado / Distribución BT',
                                    'control' => 'Tablero de Control / Automatización',
                                    'transfer' => 'Tablero de Transferencia (ATS/MTS)',
                                    'sincronizacion' => 'Tablero de Sincronización de Generadores',
                                    'remoto' => 'Tablero de Distribución Remoto',
                                    'pfcs' => 'Panel de Factor de Potencia / Corrección de FP',
                                    'medicion' => 'Tablero de Medición / Centro de Carga',
                                    'variadores' => 'Tablero con Variadores de Frecuencia (VFD)',
                                    'arrancadores' => 'Tablero con Arrancadores Suaves (SS)',
                                    'ups' => 'Tablero UPS / Respaldo',
                                    'otro' => 'Otro',
                                ])
                                ->live(),

                            TextInput::make('other_board_type')
                                ->label('Especifique el tipo de tablero')
                                ->required(fn ($get) => $get('board_type') === 'otro')
                                ->visible(fn ($get) => $get('board_type') === 'otro')
                                ->maxLength(255),
                        ]),

                    Fieldset::make('Tipo de Trabajo')
                        ->schema([
                            Radio::make('delivery_type')
                                ->label('¿Qué se requiere?')
                                ->required()
                                ->options([
                                    'tablero' => 'Tablero completo',
                                    'gabinete' => 'Solo gabinete / estructura',
                                    'reparacion' => 'Reparación / modificación de tablero existente',
                                ])
                                ->inline(false),

                            Radio::make('is_new_installation')
                                ->label('¿Instalación nueva o reemplazo?')
                                ->required()
                                ->options([
                                    'nueva' => 'Nueva instalación',
                                    'reemplazo' => 'Reemplazo de tablero existente',
                                    'ampliacion' => 'Ampliación de tablero existente',
                                ])
                                ->inline(false),
                        ]),

                    Fieldset::make('Función y Cargas')
                        ->schema([
                            Textarea::make('board_function')
                                ->label('Función principal del tablero')
                                ->required()
                                ->rows(3)
                                ->maxLength(1000)
                                ->placeholder('Ej.: Distribución principal del edificio, alimentación de motores, control de alumbrado...'),

                            Grid::make(2)->schema([
                                Textarea::make('loads_to_feed')
                                    ->label('Cargas a alimentar (descripción)')
                                    ->rows(3)
                                    ->maxLength(1000)
                                    ->placeholder('Ej.: 5 motores de 15 kW, 20 circuitos de alumbrado...'),

                                TextInput::make('number_of_circuits')
                                    ->label('N.° estimado de salidas / circuitos')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(9999),
                            ]),
                        ]),
                ]),

            Step::make('Instalación y Eléctrico')
                ->icon('heroicon-o-bolt')
                ->schema([
                    Fieldset::make('Ubicación y Ambiente')
                        ->schema([
                            Radio::make('location_type')
                                ->label('Ubicación del tablero')
                                ->required()
                                ->options([
                                    'interior' => 'Interior',
                                    'exterior' => 'Exterior',
                                ])
                                ->inline(true)
                                ->live()
                                ->afterStateUpdated(fn ($set) => $set('ip_rating', null)),

                            Select::make('special_environment')
                                ->label('Ambiente especial')
                                ->multiple()
                                ->options([
                                    'marino' => 'Marino / salino',
                                    'minero' => 'Minero / polvo fino',
                                    'humedo' => 'Húmedo / condensación',
                                    'corrosivo' => 'Corrosivo (gases, ácidos)',
                                    'polvoriento' => 'Polvoriento (general)',
                                    'explosivo' => 'Zona ATEX / explosivo',
                                    'otro' => 'Otro',
                                ])
                                ->live(),

                            TextInput::make('other_special_environment')
                                ->label('Ambiente especial — especifique')
                                ->required(fn ($get) => in_array('otro', (array) ($get('special_environment') ?? [])))
                                ->visible(fn ($get) => in_array('otro', (array) ($get('special_environment') ?? [])))
                                ->maxLength(255),

                            Grid::make(2)->schema([
                                Select::make('ip_rating')
                                    ->label('Grado de protección IP')
                                    ->required()
                                    ->options(fn ($get) => match ($get('location_type')) {
                                        'interior' => [
                                            'IP20' => 'IP20 — Protección básica',
                                            'IP31' => 'IP31 — Contra goteo vertical',
                                            'IP43' => 'IP43 — Contra lluvia a 60°',
                                            'IP54' => 'IP54 — Contra polvo y salpicaduras',
                                            'IP55' => 'IP55 — Contra chorros de agua',
                                            'IP65' => 'IP65 — Hermético al polvo, chorros de agua',
                                        ],
                                        'exterior' => [
                                            'IP54' => 'IP54 — Contra polvo y salpicaduras',
                                            'IP55' => 'IP55 — Contra chorros de agua',
                                            'IP65' => 'IP65 — Hermético al polvo, chorros de agua',
                                            'IP66' => 'IP66 — Contra chorros de agua potentes',
                                            'IP67' => 'IP67 — Inmersión temporal',
                                            'IP68' => 'IP68 — Inmersión continua',
                                        ],
                                        default => [
                                            'IP20' => 'IP20', 'IP31' => 'IP31', 'IP43' => 'IP43',
                                            'IP54' => 'IP54', 'IP55' => 'IP55', 'IP65' => 'IP65',
                                            'IP66' => 'IP66', 'IP67' => 'IP67', 'IP68' => 'IP68',
                                        ],
                                    }),

                                Select::make('ik_rating')
                                    ->label('Grado de protección IK')
                                    ->required()
                                    ->options(fn ($get) => match ($get('location_type')) {
                                        'interior' => [
                                            'IK06' => 'IK06 — 1 J (uso oficina)',
                                            'IK07' => 'IK07 — 2 J (uso general)',
                                            'IK08' => 'IK08 — 5 J (industrial ligero)',
                                            'IK09' => 'IK09 — 10 J (industrial)',
                                            'IK10' => 'IK10 — 20 J (industrial pesado)',
                                        ],
                                        'exterior' => [
                                            'IK08' => 'IK08 — 5 J (industrial ligero)',
                                            'IK09' => 'IK09 — 10 J (industrial)',
                                            'IK10' => 'IK10 — 20 J (industrial pesado)',
                                        ],
                                        default => [
                                            'IK06' => 'IK06', 'IK07' => 'IK07', 'IK08' => 'IK08',
                                            'IK09' => 'IK09', 'IK10' => 'IK10',
                                        ],
                                    }),
                            ]),
                        ]),

                    Fieldset::make('Montaje y Dimensiones')
                        ->schema([
                            Select::make('mounting_type')
                                ->label('Tipo de montaje')
                                ->required()
                                ->options([
                                    'autosoportado' => 'Autosoportado (piso)',
                                    'mural' => 'Mural / adosado a pared',
                                    'rack_19' => 'Rack 19"',
                                    'pedestal' => 'Pedestal',
                                    'otro' => 'Otro',
                                ]),

                            Toggle::make('has_dimension_restrictions')
                                ->label('¿Hay restricciones de dimensiones?')
                                ->live(),

                            Fieldset::make('Dimensiones máximas permitidas')
                                ->visible(fn ($get) => (bool) $get('has_dimension_restrictions'))
                                ->schema([
                                    Grid::make(3)->schema([
                                        TextInput::make('max_height')->label('Alto máximo (mm)')->numeric()->minValue(1),
                                        TextInput::make('max_width')->label('Ancho máximo (mm)')->numeric()->minValue(1),
                                        TextInput::make('max_depth')->label('Profundidad máxima (mm)')->numeric()->minValue(1),
                                    ]),
                                ]),

                            Textarea::make('additional_installation_conditions')
                                ->label('Condiciones adicionales de instalación')
                                ->rows(2)
                                ->maxLength(500),
                        ]),

                    Fieldset::make('Parámetros Eléctricos')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('supply_voltage')
                                    ->label('Tensión de suministro (V)')
                                    ->required()
                                    ->options([
                                        '220' => '220 V',
                                        '380' => '380 V',
                                        '400' => '400 V',
                                        '440' => '440 V',
                                        '480' => '480 V',
                                        '690' => '690 V',
                                        '1000' => '1.000 V',
                                        'otro' => 'Otro',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn ($get, $set) => static::recalculateCurrent($get, $set)),

                                Select::make('electrical_system')
                                    ->label('Sistema eléctrico')
                                    ->required()
                                    ->options([
                                        'trifasico' => 'Trifásico (3F + N)',
                                        'bifasico' => 'Bifásico (2F)',
                                        'monofasico' => 'Monofásico (1F + N)',
                                        'dc' => 'Corriente continua (DC)',
                                        'otro' => 'Otro',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn ($get, $set) => static::recalculateCurrent($get, $set)),
                            ]),

                            Grid::make(2)->schema([
                                TextInput::make('supply_voltage_other')
                                    ->label('Tensión — especifique (V)')
                                    ->required(fn ($get) => $get('supply_voltage') === 'otro')
                                    ->visible(fn ($get) => $get('supply_voltage') === 'otro')
                                    ->numeric()
                                    ->maxLength(20),

                                TextInput::make('electrical_system_other')
                                    ->label('Sistema eléctrico — especifique')
                                    ->required(fn ($get) => $get('electrical_system') === 'otro')
                                    ->visible(fn ($get) => $get('electrical_system') === 'otro')
                                    ->maxLength(100),
                            ]),

                            Grid::make(3)->schema([
                                TextInput::make('estimated_power')
                                    ->label('Potencia estimada')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->live(debounce: 600)
                                    ->afterStateUpdated(fn ($get, $set) => static::recalculateCurrent($get, $set))
                                    ->suffix(fn ($get) => $get('power_unit') ?? 'kW'),

                                Select::make('power_unit')
                                    ->label('Unidad')
                                    ->options(['kW' => 'kW', 'kVA' => 'kVA'])
                                    ->default('kW')
                                    ->live()
                                    ->afterStateUpdated(fn ($get, $set) => static::recalculateCurrent($get, $set)),

                                TextInput::make('nominal_current')
                                    ->label('Corriente nominal (A)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->hint('Calculado automáticamente.'),
                            ]),

                            Grid::make(2)->schema([
                                Select::make('frequency')
                                    ->label('Frecuencia')
                                    ->required()
                                    ->options(['50' => '50 Hz', '60' => '60 Hz', 'otro' => 'Otro'])
                                    ->live(),

                                TextInput::make('other_frequency')
                                    ->label('Frecuencia — especifique (Hz)')
                                    ->required(fn ($get) => $get('frequency') === 'otro')
                                    ->visible(fn ($get) => $get('frequency') === 'otro')
                                    ->numeric()
                                    ->maxLength(20),
                            ]),

                            Select::make('required_protections')
                                ->label('Protecciones requeridas')
                                ->required()
                                ->multiple()
                                ->options([
                                    'interruptor_automatico' => 'Interruptor automático (termomagnético)',
                                    'diferencial' => 'Interruptor diferencial (RCD)',
                                    'fusible' => 'Fusibles',
                                    'relevo_sobrecarga' => 'Relevo de sobrecarga',
                                    'relevo_falla_tierra' => 'Relevo de falla a tierra',
                                    'proteccion_tension' => 'Protección de tensión (sub/sobre)',
                                    'proteccion_corriente' => 'Protección de corriente diferencial',
                                    'descargador_tension' => 'Descargador de sobretensión (SPD)',
                                    'otro' => 'Otro',
                                ]),

                            Select::make('preferred_brands')
                                ->label('Marcas preferidas de protecciones')
                                ->multiple()
                                ->options([
                                    'schneider' => 'Schneider Electric',
                                    'siemens' => 'Siemens',
                                    'abb' => 'ABB',
                                    'legrand' => 'Legrand',
                                    'eaton' => 'Eaton',
                                    'chint' => 'CHINT',
                                    'hager' => 'Hager',
                                    'weidmuller' => 'Weidmuller',
                                    'phoenix' => 'Phoenix Contact',
                                    'otro' => 'Otro / Sin preferencia',
                                ]),
                        ]),

                    Fieldset::make('Diseño Constructivo')
                        ->schema([
                            Select::make('cabinet_material')
                                ->label('Material del gabinete')
                                ->required()
                                ->options([
                                    'acero_pintado' => 'Acero pintado (estándar, económico)',
                                    'acero_galvanizado' => 'Acero galvanizado (mayor resistencia a corrosión)',
                                    'acero_inoxidable' => 'Acero inoxidable 304 (ambientes corrosivos)',
                                    'acero_inox_316' => 'Acero inoxidable 316 (marino / alta corrosión)',
                                    'fibra_vidrio' => 'Fibra de vidrio GRP (zonas ATEX / exterior extremo)',
                                    'poliester' => 'Poliéster termoestable (alta resistencia UV)',
                                    'aluminio' => 'Aluminio (liviano, buena disipación térmica)',
                                ]),

                            Grid::make(2)->schema([
                                Select::make('special_color')
                                    ->label('Color del gabinete')
                                    ->options([
                                        '7035' => 'RAL 7035 — Gris claro (estándar industria)',
                                        '7016' => 'RAL 7016 — Gris antracita',
                                        '9016' => 'RAL 9016 — Blanco tráfico',
                                        '9005' => 'RAL 9005 — Negro intenso',
                                        '5010' => 'RAL 5010 — Azul genciana',
                                        '6005' => 'RAL 6005 — Verde musgo',
                                        'otro' => 'Otro (especificar en observaciones)',
                                    ])
                                    ->default('7035'),

                                Select::make('ventilation_type')
                                    ->label('Tipo de ventilación')
                                    ->required()
                                    ->options([
                                        'natural' => 'Natural (rejillas)',
                                        'forzada' => 'Forzada (ventiladores)',
                                        'sellado' => 'Sellado (sin ventilación directa)',
                                        'climatizado' => 'Climatizado (aire acondicionado)',
                                    ]),
                            ]),

                            Select::make('future_expansion')
                                ->label('¿Considera expansión futura?')
                                ->required()
                                ->options([
                                    'no' => 'No, sin espacio adicional',
                                    '10' => 'Sí, ~10% de espacio libre',
                                    '20' => 'Sí, ~20% de espacio libre',
                                    '30' => 'Sí, ~30% de espacio libre',
                                    'otro' => 'Sí, otro porcentaje (indicar en observaciones)',
                                ]),
                        ]),
                ]),

            Step::make('Documentación del Tablero')
                ->icon('heroicon-o-paper-clip')
                ->schema([
                    Fieldset::make('Archivos del ítem')
                        ->schema([
                            FileUpload::make('load_list_file')
                                ->label('Lista de cargas (archivo)')
                                ->hint('Excel, PDF u otro documento con el detalle de cargas.')
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'text/csv',
                                    'application/octet-stream',
                                ])
                                ->maxSize(10240)
                                ->disk('local')
                                ->directory('solicitudes/cargas'),

                            FileUpload::make('unilineal_diagram')
                                ->label('Diagrama unilineal (si existe)')
                                ->hint('Formatos aceptados: PDF, PNG, JPG, DWG.')
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'image/png',
                                    'image/jpeg',
                                    'application/acad',
                                    'application/octet-stream',
                                ])
                                ->maxSize(20480)
                                ->disk('local')
                                ->directory('solicitudes/unilineales'),

                            FileUpload::make('mechanical_plans')
                                ->label('Planos mecánicos o de espacio (si existen)')
                                ->hint('Adjunte planos de la sala eléctrica o espacio físico disponible.')
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'image/png',
                                    'image/jpeg',
                                    'application/octet-stream',
                                ])
                                ->maxSize(20480)
                                ->disk('local')
                                ->directory('solicitudes/planos'),
                        ]),

                    Fieldset::make('Observaciones del Tablero')
                        ->schema([
                            Textarea::make('additional_observations')
                                ->label('Observaciones adicionales de este tablero')
                                ->rows(4)
                                ->maxLength(2000)
                                ->placeholder('Requerimientos específicos de este tablero no contemplados en los campos anteriores...'),
                        ]),
                ]),
        ];
    }

    // -------------------------------------------------------------------------
    // Gestión de ítems
    // -------------------------------------------------------------------------
    public function editItem(int $index): void
    {
        $this->editingItemIndex = $index;
        $this->mountAction('tablero');
    }

    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
        $this->items = array_values($this->items);
    }

    // -------------------------------------------------------------------------
    // Auto-cálculo de corriente nominal
    // -------------------------------------------------------------------------
    protected static function recalculateCurrent($get, $set): void
    {
        $power = (float) ($get('estimated_power') ?? 0);
        $voltage = $get('supply_voltage');
        $system = $get('electrical_system');

        if ($power <= 0 || ! $voltage || $voltage === 'otro' || ! $system || $system === 'otro') {
            return;
        }

        $v = (float) $voltage;
        if ($v <= 0) {
            return;
        }

        $watts = $power * 1000;

        $current = match ($system) {
            'trifasico' => $watts / (sqrt(3) * $v),
            'bifasico' => $watts / ($v * 2),
            'monofasico', 'dc' => $watts / $v,
            default => null,
        };

        if ($current !== null) {
            $set('nominal_current', round($current, 1));
        }
    }

    // -------------------------------------------------------------------------
    // Envío del formulario
    // -------------------------------------------------------------------------
    public function submit(): void
    {
        $data = $this->form->getState();

        if (empty($this->items)) {
            Notification::make()
                ->title('Debe agregar al menos un tablero antes de enviar la solicitud.')
                ->danger()
                ->send();

            return;
        }

        $organization = Organization::first();

        $submissionFields = [
            'project_name' => $data['project_name'] ?? null,
            'installation_location' => $data['installation_location'] ?? null,
            'cost_center' => $data['cost_center'] ?? null,
            'desired_delivery_date' => $data['desired_delivery_date'] ?? null,
            'engineering_by' => $data['engineering_by'] ?? null,
            'submitter_name' => $data['contact_name'] ?? null,
            'submitter_email' => $data['contact_email'] ?? null,
            'submitter_phone' => $data['contact_phone'] ?? null,
            'submitter_company' => $data['client_company'] ?? null,
            'technical_specs_path' => $data['technical_specs'] ?? null,
            'site_photos_paths' => $data['site_photos'] ?? null,
            'raw_data' => ['outer' => $data, 'items' => $this->items],
        ];

        $itemsData = collect($this->items)->map(fn ($itemData, int $index) => [
            'organization_id' => $organization->id,
            'sort_order' => $index,
            'label' => $itemData['label'] ?? 'Tablero '.($index + 1),
            'quantity' => (int) ($itemData['quantity'] ?? 1),
            'delivery_type' => $itemData['delivery_type'] ?? null,
            'is_new_installation' => $itemData['is_new_installation'] ?? null,
            'board_type' => $itemData['board_type'] ?? null,
            'other_board_type' => $itemData['other_board_type'] ?? null,
            'board_function' => $itemData['board_function'] ?? null,
            'loads_to_feed' => $itemData['loads_to_feed'] ?? null,
            'number_of_circuits' => $itemData['number_of_circuits'] ?? null,
            'load_list_file_path' => $itemData['load_list_file'] ?? null,
            'location_type' => $itemData['location_type'] ?? null,
            'special_environment' => $itemData['special_environment'] ?? null,
            'other_special_environment' => $itemData['other_special_environment'] ?? null,
            'ip_rating' => $itemData['ip_rating'] ?? null,
            'ik_rating' => $itemData['ik_rating'] ?? null,
            'mounting_type' => $itemData['mounting_type'] ?? null,
            'has_dimension_restrictions' => (bool) ($itemData['has_dimension_restrictions'] ?? false),
            'max_height' => $itemData['max_height'] ?? null,
            'max_width' => $itemData['max_width'] ?? null,
            'max_depth' => $itemData['max_depth'] ?? null,
            'additional_installation_conditions' => $itemData['additional_installation_conditions'] ?? null,
            'supply_voltage' => $itemData['supply_voltage'] ?? null,
            'supply_voltage_other' => $itemData['supply_voltage_other'] ?? null,
            'electrical_system' => $itemData['electrical_system'] ?? null,
            'electrical_system_other' => $itemData['electrical_system_other'] ?? null,
            'estimated_power' => $itemData['estimated_power'] ?? null,
            'power_unit' => $itemData['power_unit'] ?? 'kW',
            'nominal_current' => $itemData['nominal_current'] ?? null,
            'frequency' => $itemData['frequency'] ?? null,
            'other_frequency' => $itemData['other_frequency'] ?? null,
            'required_protections' => $itemData['required_protections'] ?? null,
            'preferred_brands' => $itemData['preferred_brands'] ?? null,
            'cabinet_material' => $itemData['cabinet_material'] ?? null,
            'special_color' => $itemData['special_color'] ?? '7035',
            'ventilation_type' => $itemData['ventilation_type'] ?? null,
            'future_expansion' => $itemData['future_expansion'] ?? null,
            'unilineal_diagram_path' => $itemData['unilineal_diagram'] ?? null,
            'mechanical_plans_path' => $itemData['mechanical_plans'] ?? null,
            'additional_observations' => $itemData['additional_observations'] ?? null,
        ])->all();

        if ($this->editingSubmissionId) {
            $submission = SubmissionRequest::withoutGlobalScopes()->findOrFail($this->editingSubmissionId);
            $submission->update(array_merge($submissionFields, ['submitted_at' => now()]));
            $submission->items()->delete();
            foreach ($itemsData as $row) {
                SubmissionItem::withoutGlobalScopes()->create(
                    array_merge($row, ['submission_request_id' => $submission->id])
                );
            }
        } else {
            $referenceCode = 'SOL-'.strtoupper(Str::random(8));
            $submission = SubmissionRequest::withoutGlobalScopes()->create(array_merge($submissionFields, [
                'organization_id' => $organization->id,
                'reference_code' => $referenceCode,
                'status' => 'nueva',
                'ip_address' => request()->ip(),
                'user_agent' => substr(request()->userAgent() ?? '', 0, 300),
                'submitted_at' => now(),
            ]));
            foreach ($itemsData as $row) {
                SubmissionItem::withoutGlobalScopes()->create(
                    array_merge($row, ['submission_request_id' => $submission->id])
                );
            }

            // Notificar al solicitante (solo en nuevas solicitudes)
            if ($submission->submitter_email) {
                (new AnonymousNotifiable)
                    ->route('mail', $submission->submitter_email)
                    ->notify(new SubmissionConfirmed($submission));
            }

            // Notificar a admins y supervisores de la organización
            User::withoutGlobalScopes()
                ->where('organization_id', $organization->id)
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'supervisor']))
                ->get()
                ->each(fn (User $user) => $user->notify(new NewSubmissionReceived($submission)));
        }

        $this->referenceCode = $submission->reference_code;
        $this->submitted = true;

        $this->dispatch('draft-cleared');
    }

    public function render()
    {
        return view('livewire.public-form-wizard')
            ->layout('layouts.public-livewire', ['title' => 'Solicitud de Tableros Eléctricos']);
    }
}
