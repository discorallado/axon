<?php

namespace App\Livewire;

use App\Enums\SubmissionStatus;
use App\Models\Organization;
use App\Models\SubmissionRequest;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use App\Notifications\NewSubmissionReceived;
use App\Notifications\SubmissionConfirmed;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class PublicFormWizard extends Component implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    public array $data = [];

    public bool $submitted = false;

    public string $referenceCode = '';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('data')
            ->schema([
                Wizard::make([
                    self::contactStep(),
                    self::generalStep(),
                    self::scopeStep(),
                    self::electricalStep(),
                    self::installationStep(),
                    self::constructionStep(),
                    self::normativeStep(),
                ])
                    ->columnSpanFull()
                    ->persistStepInQueryString()
                    ->skippable(true)
                    ->submitAction(view('livewire.partials.wizard-submit-btn')),
            ]);
    }

    // ──────────────────────────────────────────────────────────
    // Step  — Información de contacto
    // ──────────────────────────────────────────────────────────
    protected static function contactStep(): Step
    {
        return Step::make('Información')
            ->icon('heroicon-o-building-office')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('project_name')
                        ->label('Nombre del Proyecto')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('client_name')
                        ->label('Cliente / Mandante')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('associated_contract')
                        ->label('Contrato Asociado')
                        ->maxLength(255),

                    TextInput::make('installation_location')
                        ->label('Ubicación de la Instalación')
                        ->required(),

                    DatePicker::make('estimated_delivery_date')
                        ->label('Fecha Estimada de Entrega'),

                    TextInput::make('contact_name')
                        ->label('Nombre del Contacto')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('contact_email')
                        ->label('Correo del Contacto')
                        ->email()
                        ->required()
                        ->maxLength(255),
                ]),
            ]);
    }


    // ──────────────────────────────────────────────────────────
    // Step 1 — Información General
    // ──────────────────────────────────────────────────────────
    protected static function generalStep(): Step
    {
        return Step::make('Información General')
            ->icon('heroicon-o-building-office')
            ->schema([

                Select::make('delivery_type')
                    ->label('Tipo de Entrega')
                    ->options([
                        'tablero' => 'Tablero Eléctrico',
                        'sala' => 'Sala Eléctrica',
                        'producto_electrico' => 'Producto Eléctrico',
                    ])
                    ->required(),


                Radio::make('is_new_installation')
                    ->label('¿Corresponde a una instalación nueva o al reemplazo de un tablero existente?')
                    ->options([
                        'nueva' => 'Instalación nueva',
                        'reemplazo' => 'Reemplazo de tablero existente',
                    ])
                    ->inline()
                    ->required(),

                Radio::make('engineering_by')
                    ->label('¿Quién desarrollará la ingeniería?')
                    ->options([
                        'cliente' => 'Cliente',
                        'oficina_externa' => 'Oficina de ingeniería externa',
                        'nuestra_empresa' => 'Nuestra empresa',
                    ])
                    ->inline()
                    ->required(),

            ]);
    }

    // ──────────────────────────────────────────────────────────
    // Step 2 — Función y Alcance
    // ──────────────────────────────────────────────────────────
    protected static function scopeStep(): Step
    {
        return Step::make('Función y Alcance')
            ->icon('heroicon-o-bolt')
            ->schema([
                Select::make('board_type')
                    ->label('Tipo de Tablero')
                    // ->multiple()
                    ->required()
                    ->options([
                        'fuerza' => 'Fuerza',
                        'automatizacion' => 'Automatización y Control',
                        'transferencia_ats' => 'Transferencia ATS',
                        'banco_condensadores' => 'Banco de Condensadores',
                        'medicion' => 'Medición',
                        'ccm' => 'CCM',
                        'distribucion' => 'Distribución',
                        'otro' => 'Otro',
                    ]),

                Textarea::make('board_function')
                    ->label('Objetivo o Función del Tablero')
                    ->required()
                    ->rows(4),

                Textarea::make('loads_description')
                    ->label('Cargas que Serán Alimentadas')
                    ->rows(4),

                TextInput::make('estimated_power')
                    ->label('Potencia Total Estimada')
                    ->placeholder('kW o kVA'),

                Toggle::make('has_critical_loads')
                    ->reactive()
                    ->label('¿Existen Cargas Críticas?'),
                Fieldset::make('Respaldo para Cargas Críticas')
                    ->visible(fn($get) => (bool) $get('has_critical_loads'))
                    ->schema([
                        Toggle::make('ups_backup')
                            ->reactive()
                            ->label('¿Requiere Respaldo mediante UPS?')
                            ->visible(fn($get) => (bool) $get('has_critical_loads')),

                        Toggle::make('generator_backup')
                            ->reactive()
                            ->label('¿Requiere Respaldo mediante Generador?')
                            ->visible(fn($get) => (bool) $get('has_critical_loads')),
                    ]),

                Toggle::make('requires_energy_monitoring')
                    ->label('¿Requiere Medición o Monitoreo de Energía?'),

                Toggle::make('requires_communication')
                    ->reactive()

                    ->label('¿El Tablero debe Comunicarse con Otros Sistemas?'),

                Select::make('communication_type')
                    ->label('Tipo de Comunicación Requerida')
                    ->multiple()
                    ->options([
                        'modbus_rtu' => 'Modbus RTU',
                        'modbus_tcp' => 'Modbus TCP',
                        'profinet' => 'Profinet',
                        'ethernet_ip' => 'Ethernet/IP',
                        'bacnet' => 'BACnet',
                        'opc_ua' => 'OPC-UA',
                        'otro' => 'Otro',
                    ])
                    ->visible(fn($get) => (bool) $get('requires_communication'))
                    ->required(fn($get) => (bool) $get('requires_communication')),
            ]);
    }

    // ──────────────────────────────────────────────────────────
    // Step 3 — Características Eléctricas
    // ──────────────────────────────────────────────────────────
    protected static function electricalStep(): Step
    {
        return Step::make('Características Eléctricas')
            ->icon('heroicon-o-cpu-chip')
            ->schema([
                Grid::make(2)->schema([

                    Select::make('supply_voltage')
                        ->label('Tensión de Alimentación')
                        ->reactive()
                        ->options([
                            '230' => '230 V',
                            '400' => '400 V',
                            '480' => '480 V',
                            '690' => '690 V',
                            'otro' => 'Otro',
                        ])
                        ->required(),
                    TextInput::make('other_supply_voltage')
                        ->label('Otra Tensión de Alimentación')
                        ->required()
                        ->suffix('V')
                        ->visible(fn($get) => $get('supply_voltage') === 'otro'),
                ]),

                Select::make('electrical_system')
                    ->label('Tipo de Sistema Eléctrico')
                    ->reactive()
                    ->options([
                        'monofasico' => 'Monofásico',
                        'bifasico' => 'Bifásico',
                        'trifasico' => 'Trifásico',
                        'dc' => 'Corriente Continua (DC)',
                        'otro' => 'Otro',
                    ])
                    ->required(),
                TextInput::make('other_electrical_system')
                    ->label('Otro sistema eléctrico')
                    ->required()
                    ->visible(fn($get) => $get('electrical_system') === 'otro'),


                // Select::make('grounding_system')
                //     ->label('Sistema de Puesta a Tierra')
                //     ->options([
                //         'tt' => 'TT',
                //         'tn_s' => 'TN-S',
                //         'tn_c' => 'TN-C',
                //         'tn_c_s' => 'TN-C-S',
                //         'it' => 'IT',
                //         'otro' => 'Otro',
                //     ])
                //     ->required(),

                Grid::make(3)->schema([
                    TextInput::make('nominal_current')
                        ->label('Corriente Nominal (A)')
                        ->numeric()
                        ->suffix('A'),

                    TextInput::make('short_circuit_current')
                        ->label('Corriente de Cortocircuito (kA)')
                        ->numeric()
                        ->suffix('kA'),

                    TextInput::make('distance_from_main')
                        ->label('Distancia desde Alimentación Principal')
                        ->placeholder('metros'),
                ]),

                Select::make('required_protections')
                    ->label('Protecciones Requeridas')
                    ->multiple()
                    ->required()
                    ->options([
                        'interruptor_automatico' => 'Interruptor Automático (MCB)',
                        'diferencial' => 'Diferencial (RCD)',
                        'guardamotor' => 'Guardamotor (MMS)',
                        'sobretension' => 'Descargador de Sobretensión (SPD)',
                        'fusibles' => 'Fusibles',
                        'contactor' => 'Contactor',
                        'rele_proteccion' => 'Relé de Protección',
                        'arco_electrico' => 'Protección Arco Eléctrico',
                    ]),

                Toggle::make('requires_selectivity')
                    ->label('¿Se Requiere Selectividad entre Protecciones?'),

                TextInput::make('preferred_protection_brand')
                    ->label('Preferencia de Marca de Protecciones')
                    ->placeholder('Ej: Schneider, ABB, Siemens…'),
            ]);
    }

    // ──────────────────────────────────────────────────────────
    // Step 4 — Condiciones de Instalación
    // ──────────────────────────────────────────────────────────
    protected static function installationStep(): Step
    {
        return Step::make('Condiciones de Instalación')
            ->icon('heroicon-o-map-pin')
            ->schema([
                Select::make('installation_environment')
                    ->label('Tipo de Ambiente de Instalación')
                    // ->multiple()
                    ->reactive()
                    ->required()
                    ->options([
                        'interior' => 'Interior',
                        'exterior' => 'Exterior',
                        'industrial' => 'Industrial',
                        'minero' => 'Minero',
                        'marino' => 'Marino',
                        'corrosivo' => 'Corrosivo',
                        'polvoriento' => 'Polvoriento',
                        'humedo' => 'Húmedo',
                        'otro' => 'Otro',
                    ]),
                TextInput::make('other_installation_environment')
                    ->label('Otro ambiente de instalación')
                    ->required()
                    ->reactive()
                    ->visible(fn($get) => $get('installation_environment') === 'otro'),

                Grid::make(3)->schema([
                    TextInput::make('min_temperature')
                        ->label('Temperatura Mínima Ambiente')
                        ->numeric()
                        ->suffix('°C'),

                    TextInput::make('max_temperature')
                        ->label('Temperatura Máxima Ambiente')
                        ->numeric()
                        ->suffix('°C'),

                    TextInput::make('installation_altitude')
                        ->label('Altitud de Instalación')
                        ->numeric()
                        ->suffix('msnm'),
                ]),

                Grid::make(2)->schema([
                    // TextInput::make('ip_rating')
                    //     ->label('Grado de Protección IP')
                    //     ->placeholder('Ej: IP54')
                    //     ->required(),
                    Select::make('installation_environment')
                        ->label('Tipo de Ambiente de Instalación')
                        // ->multiple()
                        ->reactive()
                        ->required()
                        ->options([
                            'IP20' => 'IP20 — Interior, sala eléctrica, sin riesgo de líquidos',
                            'IP31' => 'IP31 — Interior, ambiente seco',
                            'IP41' => 'IP41 — Interior, protección extra contra polvo',
                            'IP54' => 'IP54 — Interior/exterior protegido, polvo y salpicaduras',
                            'IP55' => 'IP55 — Exterior, chorros de agua y polvo (estándar RIC)',
                            'IP65' => 'IP65 — Exterior, estanco al polvo, lavado a presión',
                            'IP66' => 'IP66 — Exterior, condiciones severas (costero)',
                        ]),

                    // TextInput::make('ik_rating')
                    //     ->label('Grado de Protección IK')
                    //     ->placeholder('Ej: IK08'),
                    Select::make('installation_environment')
                        ->label('Tipo de Ambiente de Instalación')
                        // ->multiple()
                        ->reactive()
                        ->required()
                        ->options([
                            'IK07' => 'IK07 — Interior, zonas de tránsito normal',
                            'IK08' => 'IK08 — Interior, zonas con riesgo de golpes',
                            'IK10' => 'IK10 — Exterior o industrial, riesgo de impactos fuertes',
                        ]),
                ]),

                Toggle::make('space_restrictions')
                    ->label('¿Existen Restricciones de Espacio para la Instalación?'),

                Textarea::make('space_description')
                    ->label('Descripción del Espacio Disponible')
                    ->rows(3)
                    ->visible(fn($get) => (bool) $get('space_restrictions'))
                    ->required(fn($get) => (bool) $get('space_restrictions')),
            ]);
    }

    // ──────────────────────────────────────────────────────────
    // Step 5 — Diseño Constructivo
    // ──────────────────────────────────────────────────────────
    protected static function constructionStep(): Step
    {
        return Step::make('Diseño Constructivo')
            ->icon('heroicon-o-squares-2x2')
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('height_mm')
                        ->label('Alto')
                        ->numeric()
                        ->suffix('mm'),

                    TextInput::make('width_mm')
                        ->label('Ancho')
                        ->numeric()
                        ->suffix('mm'),

                    TextInput::make('depth_mm')
                        ->label('Profundidad')
                        ->numeric()
                        ->suffix('mm'),
                ]),

                Select::make('cabinet_material')
                    ->label('Material del Gabinete')
                    ->options([
                        'acero_pintado' => 'Acero Pintado',
                        'inox_304' => 'Acero Inoxidable 304',
                        'inox_316' => 'Acero Inoxidable 316',
                        'poliester' => 'Poliéster',
                        'plastico' => 'Plástico',
                        'otro' => 'Otro',
                    ])
                    ->required(),

                // TextInput::make('special_color')
                //     ->label('Color Especial / RAL')
                //     ->placeholder('Ej: RAL 7035'),
                Select::make('special_color')
                    ->label('Color Especial / RAL')
                    // ->multiple()
                    ->reactive()
                    ->required()
                    ->options([
                        '7035' => 'RAL 7035 — Gris claro (estándar)',
                        '7032' => 'RAL 7032 — Gris guijarro',
                        '7016' => 'RAL 7016 — Gris antracita',
                        '9002' => 'RAL 9002 — Blanco grisáceo',
                        '9005' => 'RAL 9005 — Negro intenso',
                        '9006' => 'RAL 9006 — Aluminio blanco',
                        'otro' => 'Otro (especificar código RAL)',
                    ]),

                Select::make('mounting_type')
                    ->label('Tipo de Montaje')
                    ->options([
                        'mural' => 'Mural',
                        'autosoportado' => 'Autosoportado',
                        'piso' => 'Piso',
                        'rack' => 'Rack',
                        'otro' => 'Otro',
                    ])
                    ->required(),

                Grid::make(2)->schema([
                    Select::make('cable_entry_location')
                        ->label('Ingreso de Cables')
                        // ->multiple()
                        ->options([
                            'superior' => 'Superior',
                            'inferior' => 'Inferior',
                            'laterales' => 'Laterales',
                            'posterior' => 'Posterior',
                        ]),

                    Select::make('cable_exit_location')
                        ->label('Salida de Cables')
                        // ->multiple()
                        ->options([
                            'superior' => 'Superior',
                            'inferior' => 'Inferior',
                            'laterales' => 'Laterales',
                            'posterior' => 'Posterior',
                        ]),
                ]),

                Select::make('ventilation_type')
                    ->label('Tipo de Ventilación')
                    ->options([
                        'natural' => 'Natural',
                        'forzada' => 'Forzada',
                        'aire_acondicionado' => 'Aire Acondicionado',
                        'intercambiador_calor' => 'Intercambiador de Calor',
                        'sin_ventilacion' => 'Sin Ventilación',
                    ])
                    ->required(),

                Toggle::make('requires_interior_lighting')
                    ->label('¿Requiere Iluminación Interior?'),

                Select::make('future_expansion')
                    ->label('¿Se Contempla Ampliación Futura? Extra al 25% normativo')
                    ->options([
                        'no' => 'No',
                        'entre_25_50' => 'Entre 25% y 50%',
                        'superior_50' => 'Superior al 50%',
                    ])
                    ->required(),

                Select::make('required_signage')
                    ->label('Tipos de Señaléticas Requeridas (además de las normativas)')
                    ->multiple()
                    ->options([
                        'arc_flash' => 'Arc Flash',
                        'personalizadas' => 'Personalizadas',
                    ]),
            ]);
    }

    // ──────────────────────────────────────────────────────────
    // Step 6 — Normativa y Documentación
    // ──────────────────────────────────────────────────────────
    protected static function normativeStep(): Step
    {
        return Step::make('Normativa y Documentación')
            ->icon('heroicon-o-document-text')
            ->schema([
                Select::make('applicable_normative')
                    ->label('Normativa Aplicable')
                    ->multiple()
                    ->required()
                    ->options([
                        'ric_n2' => 'RIC N°2',
                        'iec_61439' => 'IEC 61439',
                        'iec_60204' => 'IEC 60204-1',
                        'nfpa_70' => 'NFPA 70',
                        'ul_508a' => 'UL 508A',
                        'otra' => 'Otra',
                    ]),

                Toggle::make('has_load_list')
                    ->label('¿Dispone de Listado de Cargas?'),

                FileUpload::make('load_list_file')
                    ->label('Adjuntar Listado de Cargas')
                    ->multiple()
                    ->directory('submissions/tmp/load-lists')
                    ->visible(fn($get) => (bool) $get('has_load_list')),

                Toggle::make('has_existing_plans')
                    ->label('¿Dispone de Planos Existentes?'),

                FileUpload::make('unilineal_diagram')
                    ->label('Diagrama Unilineal')
                    ->multiple()
                    ->directory('submissions/tmp/unilineal')
                    ->visible(fn($get) => (bool) $get('has_existing_plans')),

                FileUpload::make('mechanical_plans')
                    ->label('Planos Mecánicos')
                    ->multiple()
                    ->directory('submissions/tmp/mechanical')
                    ->visible(fn($get) => (bool) $get('has_existing_plans')),

                FileUpload::make('technical_specs')
                    ->label('Especificaciones Técnicas')
                    ->multiple()
                    ->directory('submissions/tmp/specs'),

                FileUpload::make('site_photos')
                    ->label('Fotografías del Sitio')
                    ->multiple()
                    ->image()
                    ->directory('submissions/tmp/photos'),

                Textarea::make('additional_observations')
                    ->label('Observaciones Adicionales')
                    ->rows(5),
            ]);
    }

    // ──────────────────────────────────────────────────────────
    // Submit
    // ──────────────────────────────────────────────────────────
    public function submit(): void
    {
        $state = $this->form->getState();

        DB::transaction(function () use ($state) {
            $org = Organization::withoutGlobalScopes()->firstOrFail();
            $refCode = 'SOL-' . strtoupper(Str::random(3)) . '-' . now()->format('ymd');

            $submission = SubmissionRequest::create([
                'organization_id' => $org->id,
                'reference_code' => $refCode,
                'status' => SubmissionStatus::Nueva,
                'submitter_name' => $state['contact_name'] ?? null,
                'submitter_email' => $state['contact_email'] ?? null,
                'submitter_company' => $state['client_name'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit(request()->userAgent() ?? '', 290),
                'submitted_at' => now(),
            ]);

            foreach ($this->answerLabels() as $key => $label) {
                $rawValue = $state[$key] ?? null;

                if (! filled($rawValue) && $rawValue !== false) {
                    continue;
                }

                if (is_array($rawValue)) {
                    $value = implode(', ', array_filter($rawValue));
                } elseif (is_bool($rawValue)) {
                    $value = $rawValue ? 'Sí' : 'No';
                } else {
                    $value = (string) $rawValue;
                }

                $submission->answers()->create([
                    'organization_id' => $org->id,
                    'question_key' => $key,
                    'question_label' => $label,
                    'value' => $value,
                ]);
            }

            foreach ($this->fileFields() as $fieldKey) {
                foreach ($state[$fieldKey] ?? [] as $tmpPath) {
                    if (! $tmpPath) {
                        continue;
                    }
                    $newPath = 'submissions/' . $submission->id . '/' . basename((string) $tmpPath);
                    Storage::disk('local')->move((string) $tmpPath, $newPath);

                    $submission->attachments()->create([
                        'organization_id' => $org->id,
                        'disk' => 'local',
                        'path' => $newPath,
                        'original_name' => basename((string) $tmpPath),
                        'mime_type' => null,
                        'size_bytes' => Storage::disk('local')->size($newPath),
                        'uploaded_by' => null,
                    ]);
                }
            }

            SubmissionStatusHistory::create([
                'organization_id' => $org->id,
                'submission_request_id' => $submission->id,
                'from_status' => null,
                'to_status' => SubmissionStatus::Nueva,
                'changed_by' => null,
                'comment' => 'Solicitud recibida vía formulario web.',
                'created_at' => now(),
            ]);

            $admins = User::withoutGlobalScopes()
                ->where('organization_id', $org->id)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['super_admin', 'supervisor']))
                ->get();

            NotificationFacade::send($admins, new NewSubmissionReceived($submission));
            NotificationFacade::route('mail', $submission->submitter_email)
                ->notify(new SubmissionConfirmed($submission));

            $this->referenceCode = $refCode;
        });

        $this->submitted = true;
    }

    // ──────────────────────────────────────────────────────────
    // Mapeo de campos → etiquetas legibles
    // Actualiza aquí cuando cambies el formulario.
    // ──────────────────────────────────────────────────────────
    protected function answerLabels(): array
    {
        return [
            // 1. Información General
            'project_name' => 'Nombre del Proyecto',
            'client_name' => 'Cliente / Mandante',
            'associated_contract' => 'Contrato Asociado',
            'installation_location' => 'Ubicación de la Instalación',
            'estimated_delivery_date' => 'Fecha Estimada de Entrega',
            'delivery_type' => 'Tipo de Entrega',
            'is_new_installation' => 'Tipo de Instalación',
            'engineering_by' => 'Responsable de la Ingeniería',
            'contact_name' => 'Nombre del Contacto',
            'contact_email' => 'Correo del Contacto',
            // 2. Función y Alcance
            'board_type' => 'Tipo de Tablero',
            'board_function' => 'Objetivo / Función del Tablero',
            'loads_description' => 'Cargas Alimentadas',
            'estimated_power' => 'Potencia Total Estimada',
            'has_critical_loads' => '¿Cargas Críticas?',
            'ups_backup' => '¿Respaldo UPS?',
            'generator_backup' => '¿Respaldo Generador?',
            'requires_energy_monitoring' => '¿Medición / Monitoreo de Energía?',
            'requires_communication' => '¿Comunicación con Otros Sistemas?',
            'communication_type' => 'Tipo de Comunicación',
            // 3. Características Eléctricas
            'supply_voltage' => 'Tensión de Alimentación',
            'frequency' => 'Frecuencia',
            'electrical_system' => 'Sistema Eléctrico',
            'grounding_system' => 'Sistema de Puesta a Tierra',
            'nominal_current' => 'Corriente Nominal (A)',
            'short_circuit_current' => 'Corriente de Cortocircuito (kA)',
            'distance_from_main' => 'Distancia desde Alimentación Principal',
            'required_protections' => 'Protecciones Requeridas',
            'requires_selectivity' => '¿Selectividad entre Protecciones?',
            'preferred_protection_brand' => 'Preferencia de Marca (Protecciones)',
            // 4. Condiciones de Instalación
            'installation_environment' => 'Ambiente de Instalación',
            'min_temperature' => 'Temperatura Mínima (°C)',
            'max_temperature' => 'Temperatura Máxima (°C)',
            'installation_altitude' => 'Altitud de Instalación (msnm)',
            'ip_rating' => 'Grado IP',
            'ik_rating' => 'Grado IK',
            'space_restrictions' => '¿Restricciones de Espacio?',
            'space_description' => 'Descripción del Espacio Disponible',
            // 5. Diseño Constructivo
            'height_mm' => 'Alto (mm)',
            'width_mm' => 'Ancho (mm)',
            'depth_mm' => 'Profundidad (mm)',
            'cabinet_material' => 'Material del Gabinete',
            'special_color' => 'Color / RAL',
            'mounting_type' => 'Tipo de Montaje',
            'cable_entry_location' => 'Ingreso de Cables',
            'cable_exit_location' => 'Salida de Cables',
            'ventilation_type' => 'Tipo de Ventilación',
            'requires_interior_lighting' => '¿Iluminación Interior?',
            'future_expansion' => 'Ampliación Futura',
            'required_signage' => 'Señaléticas Requeridas',
            // 6. Normativa y Documentación
            'applicable_normative' => 'Normativa Aplicable',
            'has_load_list' => '¿Dispone de Listado de Cargas?',
            'has_existing_plans' => '¿Dispone de Planos Existentes?',
            'additional_observations' => 'Observaciones Adicionales',
        ];
    }

    protected function fileFields(): array
    {
        return [
            'load_list_file',
            'unilineal_diagram',
            'mechanical_plans',
            'technical_specs',
            'site_photos',
        ];
    }

    public function render(): View
    {
        return view('livewire.public-form-wizard')
            ->layout('layouts.public-livewire', ['title' => 'Solicitud de Tableros Eléctricos']);
    }
}
