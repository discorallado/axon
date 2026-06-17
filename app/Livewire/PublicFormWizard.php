<?php

namespace App\Livewire;

use App\Models\Organization;
use App\Models\SubmissionRequest;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Component;

class PublicFormWizard extends Component implements HasForms
{
    use InteractsWithForms;

    public array $data = [];

    public bool $submitted = false;

    public ?string $referenceCode = null;

    public function mount(): void
    {
        $this->form->fill();
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
            $this->needsStep(),
            $this->installationStep(),
            $this->electricalConstructiveStep(),
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
                Grid::make(2)->schema([
                    TextInput::make('project_name')
                        ->label('Nombre del Proyecto / Obra')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('client_name')
                        ->label('Empresa / Cliente')
                        ->required()
                        ->maxLength(255),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('contact_name')
                        ->label('Nombre del Contacto')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('contact_email')
                        ->label('Correo Electrónico')
                        ->email()
                        ->required()
                        ->maxLength(255),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('contact_phone')
                        ->label('Teléfono de Contacto')
                        ->tel()
                        ->maxLength(50),

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
            ]);
    }

    // -------------------------------------------------------------------------
    // Step 2 — ¿Qué necesitas?
    // -------------------------------------------------------------------------
    protected function needsStep(): Step
    {
        return Step::make('¿Qué necesitas?')
            ->icon('heroicon-o-clipboard-document-list')
            ->schema([
                Radio::make('delivery_type')
                    ->label('Tipo de entrega')
                    ->required()
                    ->options([
                        'tablero'    => 'Tablero completo',
                        'gabinete'   => 'Solo gabinete / estructura',
                        'reparacion' => 'Reparación / modificación de tablero existente',
                    ])
                    ->inline(false),

                Radio::make('is_new_installation')
                    ->label('¿Es instalación nueva o reemplazo?')
                    ->required()
                    ->options([
                        'nueva'      => 'Nueva instalación',
                        'reemplazo'  => 'Reemplazo de tablero existente',
                        'ampliacion' => 'Ampliación de tablero existente',
                    ])
                    ->inline(false),

                Radio::make('engineering_by')
                    ->label('Ingeniería básica — ¿quién la entrega?')
                    ->required()
                    ->options([
                        'csenergia' => 'CSEnergy se encarga',
                        'cliente'   => 'La entrega el cliente',
                        'conjunta'  => 'Conjunta (CSEnergy + cliente)',
                    ])
                    ->inline(false),

                Select::make('board_type')
                    ->label('Tipo de tablero')
                    ->required()
                    ->options([
                        'fuerza'         => 'Tablero de Fuerza / Potencia',
                        'alumbrado'      => 'Tablero de Alumbrado / Distribución BT',
                        'control'        => 'Tablero de Control / Automatización',
                        'transfer'       => 'Tablero de Transferencia (ATS/MTS)',
                        'sincronizacion' => 'Tablero de Sincronización de Generadores',
                        'remoto'         => 'Tablero de Distribución Remoto',
                        'pfcs'           => 'Panel de Factor de Potencia / Corrección de FP',
                        'medicion'       => 'Tablero de Medición / Centro de Carga',
                        'variadores'     => 'Tablero con Variadores de Frecuencia (VFD)',
                        'arrancadores'   => 'Tablero con Arrancadores Suaves (SS)',
                        'ups'            => 'Tablero UPS / Respaldo',
                        'otro'           => 'Otro',
                    ])
                    ->live(),

                TextInput::make('other_board_type')
                    ->label('Especifique el tipo de tablero')
                    ->required(fn ($get) => $get('board_type') === 'otro')
                    ->visible(fn ($get) => $get('board_type') === 'otro')
                    ->maxLength(255),

                Textarea::make('board_function')
                    ->label('Función principal del tablero')
                    ->required()
                    ->rows(3)
                    ->maxLength(1000)
                    ->placeholder('Ej.: Distribución principal del edificio, alimentación de motores, control de alumbrado...'),

                Textarea::make('loads_to_feed')
                    ->label('Cargas a alimentar (descripción)')
                    ->rows(3)
                    ->maxLength(1000)
                    ->placeholder('Ej.: 5 motores de 15 kW, 20 circuitos de alumbrado, 3 compresores...'),

                Grid::make(2)->schema([
                    TextInput::make('number_of_circuits')
                        ->label('Número estimado de salidas / circuitos')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(9999),

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
                ]),
            ]);
    }

    // -------------------------------------------------------------------------
    // Step 3 — ¿Dónde y cómo se instala?
    // -------------------------------------------------------------------------
    protected function installationStep(): Step
    {
        return Step::make('¿Dónde se instala?')
            ->icon('heroicon-o-building-office')
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
                        'marino'      => 'Marino / salino',
                        'minero'      => 'Minero / polvo fino',
                        'humedo'      => 'Húmedo / condensación',
                        'corrosivo'   => 'Corrosivo (gases, ácidos)',
                        'polvoriento' => 'Polvoriento (general)',
                        'explosivo'   => 'Zona ATEX / explosivo',
                        'otro'        => 'Otro',
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

                Select::make('mounting_type')
                    ->label('Tipo de montaje')
                    ->required()
                    ->options([
                        'autosoportado' => 'Autosoportado (piso)',
                        'mural'         => 'Mural / adosado a pared',
                        'rack_19'       => 'Rack 19"',
                        'pedestal'      => 'Pedestal',
                        'otro'          => 'Otro',
                    ]),

                Toggle::make('has_dimension_restrictions')
                    ->label('¿Hay restricciones de dimensiones?')
                    ->live(),

                Fieldset::make('Dimensiones máximas permitidas')
                    ->visible(fn ($get) => (bool) $get('has_dimension_restrictions'))
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('max_height')
                                ->label('Alto máximo (mm)')
                                ->numeric()
                                ->minValue(1),
                            TextInput::make('max_width')
                                ->label('Ancho máximo (mm)')
                                ->numeric()
                                ->minValue(1),
                            TextInput::make('max_depth')
                                ->label('Profundidad máxima (mm)')
                                ->numeric()
                                ->minValue(1),
                        ]),
                    ]),

                Textarea::make('additional_installation_conditions')
                    ->label('Condiciones adicionales de instalación')
                    ->rows(2)
                    ->maxLength(500),
            ]);
    }

    // -------------------------------------------------------------------------
    // Step 4 — Características Eléctricas y Constructivas
    // -------------------------------------------------------------------------
    protected function electricalConstructiveStep(): Step
    {
        return Step::make('Características Técnicas')
            ->icon('heroicon-o-bolt')
            ->schema([
                Fieldset::make('Parámetros Eléctricos')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('supply_voltage')
                                ->label('Tensión de suministro (V)')
                                ->required()
                                ->options([
                                    '220'  => '220 V',
                                    '380'  => '380 V',
                                    '400'  => '400 V',
                                    '440'  => '440 V',
                                    '480'  => '480 V',
                                    '690'  => '690 V',
                                    '1000' => '1.000 V',
                                    'otro' => 'Otro',
                                ])
                                ->live()
                                ->afterStateUpdated(fn ($get, $set) => static::recalculateCurrent($get, $set)),

                            Select::make('electrical_system')
                                ->label('Sistema eléctrico')
                                ->required()
                                ->options([
                                    'trifasico'  => 'Trifásico (3F + N)',
                                    'bifasico'   => 'Bifásico (2F)',
                                    'monofasico' => 'Monofásico (1F + N)',
                                    'dc'         => 'Corriente continua (DC)',
                                    'otro'       => 'Otro',
                                ])
                                ->live()
                                ->afterStateUpdated(fn ($get, $set) => static::recalculateCurrent($get, $set)),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('supply_voltage_other')
                                ->label('Tensión de suministro — especifique (V)')
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
                                ->hint('Se calcula automáticamente al ingresar potencia, tensión y sistema.')
                                ->live(debounce: 600),
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
                                'diferencial'            => 'Interruptor diferencial (RCD)',
                                'fusible'                => 'Fusibles',
                                'relevo_sobrecarga'      => 'Relevo de sobrecarga',
                                'relevo_falla_tierra'    => 'Relevo de falla a tierra',
                                'proteccion_tension'     => 'Protección de tensión (sub/sobre)',
                                'proteccion_corriente'   => 'Protección de corriente diferencial',
                                'descargador_tension'    => 'Descargador de sobretensión (SPD)',
                                'otro'                   => 'Otro',
                            ]),

                        Select::make('preferred_brands')
                            ->label('Marcas preferidas de protecciones')
                            ->multiple()
                            ->options([
                                'schneider'  => 'Schneider Electric',
                                'siemens'    => 'Siemens',
                                'abb'        => 'ABB',
                                'legrand'    => 'Legrand',
                                'eaton'      => 'Eaton',
                                'chint'      => 'CHINT',
                                'hager'      => 'Hager',
                                'weidmuller' => 'Weidmuller',
                                'phoenix'    => 'Phoenix Contact',
                                'otro'       => 'Otro / Sin preferencia',
                            ]),

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
                    ]),

                Fieldset::make('Diseño Constructivo')
                    ->schema([
                        Select::make('cabinet_material')
                            ->label('Material del gabinete')
                            ->required()
                            ->options([
                                'acero_pintado'     => 'Acero pintado (estándar, económico)',
                                'acero_galvanizado' => 'Acero galvanizado (mayor resistencia a corrosión)',
                                'acero_inoxidable'  => 'Acero inoxidable 304 (ambientes corrosivos / alimentario)',
                                'acero_inox_316'    => 'Acero inoxidable 316 (marino / alta corrosión)',
                                'fibra_vidrio'      => 'Fibra de vidrio GRP (zonas ATEX / exterior extremo)',
                                'poliester'         => 'Poliéster termoestable (alta resistencia UV / outdoor)',
                                'aluminio'          => 'Aluminio (liviano, buena disipación térmica)',
                            ])
                            ->hint('El ambiente seleccionado en el paso anterior orienta la elección.'),

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
                                ->default('7035')
                                ->hint('RAL 7035 es el estándar de la industria.'),

                            Select::make('ventilation_type')
                                ->label('Tipo de ventilación')
                                ->required()
                                ->options([
                                    'natural'     => 'Natural (rejillas)',
                                    'forzada'     => 'Forzada (ventiladores)',
                                    'sellado'     => 'Sellado (sin ventilación directa)',
                                    'climatizado' => 'Climatizado (aire acondicionado)',
                                ]),
                        ]),

                        Select::make('future_expansion')
                            ->label('¿Considera expansión futura?')
                            ->required()
                            ->options([
                                'no'   => 'No, sin espacio adicional',
                                '10'   => 'Sí, ~10% de espacio libre',
                                '20'   => 'Sí, ~20% de espacio libre',
                                '30'   => 'Sí, ~30% de espacio libre',
                                'otro' => 'Sí, otro porcentaje (indicar en observaciones)',
                            ]),

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
            ]);
    }

    // -------------------------------------------------------------------------
    // Step 5 — Documentación y Cierre
    // -------------------------------------------------------------------------
    protected function documentationStep(): Step
    {
        return Step::make('Documentación')
            ->icon('heroicon-o-paper-clip')
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

                FileUpload::make('site_photos')
                    ->label('Fotografías / Material Gráfico de la Solicitud')
                    ->hint('Adjunte fotografías del sitio, espacio disponible, tablero existente u otro material gráfico relevante.')
                    ->multiple()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                    ->maxSize(10240)
                    ->maxFiles(10)
                    ->disk('local')
                    ->directory('solicitudes/fotos'),

                Textarea::make('additional_observations')
                    ->label('Observaciones adicionales')
                    ->rows(4)
                    ->maxLength(2000)
                    ->placeholder('Indique cualquier requerimiento especial no contemplado en los pasos anteriores...'),
            ]);
    }

    // -------------------------------------------------------------------------
    // Auto-cálculo de corriente nominal
    // -------------------------------------------------------------------------
    protected static function recalculateCurrent($get, $set): void
    {
        $power   = (float) ($get('estimated_power') ?? 0);
        $voltage = $get('supply_voltage');
        $system  = $get('electrical_system');

        if ($power <= 0 || ! $voltage || $voltage === 'otro' || ! $system || $system === 'otro') {
            return;
        }

        $v = (float) $voltage;
        if ($v <= 0) {
            return;
        }

        $watts = $power * 1000;

        $current = match ($system) {
            'trifasico'        => $watts / (sqrt(3) * $v),
            'bifasico'         => $watts / ($v * 2),
            'monofasico', 'dc' => $watts / $v,
            default            => null,
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

        $organization = Organization::first();

        $referenceCode = 'SOL-' . strtoupper(Str::random(8));

        $submission = SubmissionRequest::create([
            'organization_id' => $organization->id,
            'reference_code'  => $referenceCode,
            'submitter_name'  => $data['contact_name'] ?? null,
            'submitter_email' => $data['contact_email'] ?? null,
            'submitter_phone' => $data['contact_phone'] ?? null,
            'project_name'    => $data['project_name'] ?? null,
            'status'          => 'nueva',
            'submitted_at'    => now(),
            'raw_data'        => $data,
        ]);

        $labels = $this->answerLabels();

        foreach ($data as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $submission->answers()->create([
                'organization_id' => $organization->id,
                'question_key'    => $key,
                'question_label'  => $labels[$key] ?? $key,
                'answer_value'    => is_array($value) ? implode(', ', $value) : (string) $value,
            ]);
        }

        $this->referenceCode = $referenceCode;
        $this->submitted     = true;

        $this->dispatch('draft-cleared');
    }

    // -------------------------------------------------------------------------
    // Etiquetas de respuestas
    // -------------------------------------------------------------------------
    protected function answerLabels(): array
    {
        return [
            'project_name'                       => 'Nombre del Proyecto / Obra',
            'client_name'                        => 'Empresa / Cliente',
            'installation_location'              => 'Ubicación de la Instalación',
            'contact_name'                       => 'Nombre del Contacto',
            'contact_email'                      => 'Correo Electrónico',
            'contact_phone'                      => 'Teléfono de Contacto',
            'cost_center'                        => 'Centro de Costo Asociado',
            'desired_delivery_date'              => 'Fecha de Entrega Deseada',
            'delivery_type'                      => 'Tipo de Entrega',
            'is_new_installation'                => '¿Nueva instalación o reemplazo?',
            'engineering_by'                     => 'Ingeniería básica',
            'board_type'                         => 'Tipo de Tablero',
            'other_board_type'                   => 'Tipo de Tablero (especificado)',
            'board_function'                     => 'Función principal del tablero',
            'loads_to_feed'                      => 'Cargas a alimentar',
            'number_of_circuits'                 => 'Número de salidas / circuitos',
            'load_list_file'                     => 'Lista de cargas (archivo)',
            'location_type'                      => 'Ubicación del tablero',
            'special_environment'                => 'Ambiente especial',
            'other_special_environment'          => 'Ambiente especial (especificado)',
            'ip_rating'                          => 'Grado de protección IP',
            'ik_rating'                          => 'Grado de protección IK',
            'mounting_type'                      => 'Tipo de montaje',
            'has_dimension_restrictions'         => '¿Restricciones de dimensiones?',
            'max_height'                         => 'Alto máximo',
            'max_width'                          => 'Ancho máximo',
            'max_depth'                          => 'Profundidad máxima',
            'additional_installation_conditions' => 'Condiciones adicionales de instalación',
            'supply_voltage'                     => 'Tensión de suministro',
            'supply_voltage_other'               => 'Tensión de suministro (especificada)',
            'electrical_system'                  => 'Sistema eléctrico',
            'electrical_system_other'            => 'Sistema eléctrico (especificado)',
            'estimated_power'                    => 'Potencia estimada',
            'power_unit'                         => 'Unidad de potencia',
            'nominal_current'                    => 'Corriente nominal',
            'frequency'                          => 'Frecuencia',
            'other_frequency'                    => 'Frecuencia (especificada)',
            'required_protections'               => 'Protecciones requeridas',
            'preferred_brands'                   => 'Marcas preferidas de protecciones',
            'unilineal_diagram'                  => 'Diagrama unilineal',
            'cabinet_material'                   => 'Material del gabinete',
            'special_color'                      => 'Color del gabinete',
            'ventilation_type'                   => 'Tipo de ventilación',
            'future_expansion'                   => '¿Expansión futura?',
            'mechanical_plans'                   => 'Planos mecánicos o de espacio',
            'has_technical_specs'                => '¿Tiene especificaciones técnicas?',
            'technical_specs'                    => 'Especificaciones técnicas / pliego',
            'site_photos'                        => 'Fotografías / Material Gráfico',
            'additional_observations'            => 'Observaciones adicionales',
        ];
    }

    public function render()
    {
        return view('livewire.public-form-wizard')
            ->layout('layouts.public-livewire', ['title' => 'Solicitud de Tableros Eléctricos']);
    }
}
