<?php

namespace App\Livewire;

use App\Models\FormTemplate;
use App\Models\SubmissionRequest;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use App\Notifications\NewSubmissionReceived;
use App\Notifications\SubmissionConfirmed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
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

                    Step::make('Contacto')
                        ->description('¿Quién hace la solicitud?')
                        ->icon('heroicon-o-user')
                        ->columns(2)
                        ->schema([
                            TextInput::make('submitter_name')
                                ->label('Nombre completo')
                                ->required()
                                ->maxLength(150)
                                ->columnSpanFull(),

                            TextInput::make('submitter_email')
                                ->label('Correo electrónico')
                                ->email()
                                ->required()
                                ->maxLength(255),

                            TextInput::make('submitter_phone')
                                ->label('Teléfono')
                                ->tel()
                                ->maxLength(30),

                            TextInput::make('submitter_company')
                                ->label('Empresa')
                                ->maxLength(150)
                                ->columnSpanFull(),
                        ]),

                    Step::make('Proyecto')
                        ->description('Información general del proyecto')
                        ->icon('heroicon-o-building-office-2')
                        ->columns(2)
                        ->schema([
                            Select::make('project_type')
                                ->label('Tipo de proyecto')
                                ->required()
                                ->options([
                                    'data_center' => 'Data Center',
                                    'edificio' => 'Edificio / Comercial',
                                    'industrial' => 'Industrial / Minería',
                                    'hospital' => 'Hospital / Salud',
                                    'infraestructura' => 'Infraestructura crítica',
                                    'otro' => 'Otro',
                                ]),

                            TextInput::make('project_location')
                                ->label('Ubicación del proyecto')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Ciudad, Región'),

                            DatePicker::make('required_date')
                                ->label('Fecha requerida de entrega')
                                ->minDate(now()->addDays(7))
                                ->displayFormat('d/m/Y'),

                            Textarea::make('project_notes')
                                ->label('Descripción del proyecto')
                                ->rows(4)
                                ->maxLength(2000)
                                ->columnSpanFull(),
                        ]),

                    Step::make('Tablero')
                        ->description('Especificaciones técnicas')
                        ->icon('heroicon-o-bolt')
                        ->columns(2)
                        ->schema([
                            Select::make('board_type')
                                ->label('Tipo de tablero')
                                ->required()
                                ->options([
                                    'distribucion' => 'Tablero de distribución general',
                                    'subdistribucion' => 'Subtablero de distribución',
                                    'control' => 'Tablero de control',
                                    'qgbt' => 'QGBT (Cuadro General Baja Tensión)',
                                    'transferencia' => 'Tablero de transferencia',
                                    'compensacion' => 'Banco de compensación reactiva',
                                    'otro' => 'Otro',
                                ]),

                            Select::make('nominal_voltage')
                                ->label('Tensión nominal')
                                ->required()
                                ->options([
                                    '220V' => '220 V',
                                    '380V' => '380 V',
                                    '400V' => '400 V',
                                    '440V' => '440 V',
                                    '13.8kV' => '13,8 kV',
                                    'otro' => 'Otro',
                                ]),

                            TextInput::make('nominal_current')
                                ->label('Corriente nominal')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->suffix('A'),

                            TextInput::make('circuit_count')
                                ->label('N° de circuitos')
                                ->numeric()
                                ->minValue(1),

                            Select::make('standard')
                                ->label('Norma aplicable')
                                ->options([
                                    'IEC_61439' => 'IEC 61439',
                                    'NCh_IEC' => 'NCh/IEC (Chile)',
                                    'NEMA' => 'NEMA',
                                    'no_definida' => 'Por definir',
                                ]),

                            Textarea::make('specs_notes')
                                ->label('Notas técnicas adicionales')
                                ->rows(3)
                                ->maxLength(2000)
                                ->columnSpanFull(),
                        ]),

                    Step::make('Adjuntos')
                        ->description('Documentos y confirmación')
                        ->icon('heroicon-o-paper-clip')
                        ->schema([
                            FileUpload::make('attachments')
                                ->label('Adjuntar archivos (planos, especificaciones, etc.)')
                                ->multiple()
                                ->maxSize(20480)
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'image/*',
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                ])
                                ->helperText('PDF, imágenes y Excel. Máx. 20 MB por archivo.')
                                ->disk('local')
                                ->directory('tmp-uploads')
                                ->columnSpanFull(),

                            Textarea::make('final_notes')
                                ->label('Comentarios finales')
                                ->rows(3)
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ]),

                ])
                    ->submitAction(view('livewire.partials.wizard-submit-btn')),
            ]);
    }

    public function submit(): void
    {
        $state = $this->form->getState();

        $template = FormTemplate::withoutGlobalScopes()
            ->where('slug', 'tableros-electricos')
            ->where('is_active', true)
            ->firstOrFail();

        DB::transaction(function () use ($state, $template) {
            $initialStatus = $template->organization->submissionStatuses()
                ->where('is_initial', true)
                ->firstOrFail();

            $refCode = 'SOL-'.strtoupper(Str::random(3)).'-'.now()->format('ymd');

            $submission = SubmissionRequest::create([
                'organization_id' => $template->organization_id,
                'form_template_id' => $template->id,
                'template_version' => $template->current_version,
                'reference_code' => $refCode,
                'status_id' => $initialStatus->id,
                'submitter_name' => $state['submitter_name'],
                'submitter_email' => $state['submitter_email'],
                'submitter_phone' => $state['submitter_phone'] ?? null,
                'submitter_company' => $state['submitter_company'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit(request()->userAgent() ?? '', 290),
                'submitted_at' => now(),
            ]);

            $answerMeta = [
                'project_type' => 'Tipo de proyecto',
                'project_location' => 'Ubicación del proyecto',
                'required_date' => 'Fecha requerida de entrega',
                'project_notes' => 'Descripción del proyecto',
                'board_type' => 'Tipo de tablero',
                'nominal_voltage' => 'Tensión nominal',
                'nominal_current' => 'Corriente nominal (A)',
                'circuit_count' => 'N° de circuitos',
                'standard' => 'Norma aplicable',
                'specs_notes' => 'Notas técnicas adicionales',
                'final_notes' => 'Comentarios finales',
            ];

            foreach ($answerMeta as $key => $label) {
                $value = $state[$key] ?? null;
                if (! filled($value)) {
                    continue;
                }
                $submission->answers()->create([
                    'organization_id' => $template->organization_id,
                    'question_key' => $key,
                    'question_label' => $label,
                    'value' => $value,
                ]);
            }

            // Mover adjuntos desde directorio temporal a carpeta de la submission
            foreach ($state['attachments'] ?? [] as $tmpPath) {
                if (! $tmpPath) {
                    continue;
                }
                $newPath = 'submissions/'.$submission->id.'/'.basename((string) $tmpPath);
                Storage::disk('local')->move((string) $tmpPath, $newPath);

                $submission->attachments()->create([
                    'organization_id' => $template->organization_id,
                    'disk' => 'local',
                    'path' => $newPath,
                    'original_name' => basename((string) $tmpPath),
                    'mime_type' => null,
                    'size_bytes' => Storage::disk('local')->size($newPath),
                    'uploaded_by' => null,
                ]);
            }

            SubmissionStatusHistory::create([
                'organization_id' => $template->organization_id,
                'submission_request_id' => $submission->id,
                'from_status_id' => null,
                'to_status_id' => $initialStatus->id,
                'changed_by' => null,
                'comment' => 'Solicitud recibida vía formulario web.',
                'created_at' => now(),
            ]);

            $admins = User::withoutGlobalScopes()
                ->where('organization_id', $template->organization_id)
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super_admin', 'supervisor']))
                ->get();

            NotificationFacade::send($admins, new NewSubmissionReceived($submission));
            NotificationFacade::route('mail', $submission->submitter_email)
                ->notify(new SubmissionConfirmed($submission));

            $this->referenceCode = $refCode;
        });

        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.public-form-wizard')
            ->layout('layouts.public-livewire', ['title' => 'Solicitud de Tableros Eléctricos']);
    }
}
