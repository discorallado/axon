<?php

namespace App\Filament\Actions;

use App\Enums\ProjectPriority;
use App\Enums\SubmissionStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\SubmissionRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateProjectFromSubmissionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'create_project_from_submission';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('projects.actions.create_from_submission'))
            ->icon('heroicon-o-briefcase')
            ->color('success')
            ->visible(fn (SubmissionRequest $record): bool => $record->status === SubmissionStatus::Aprobada && $record->project === null)
            ->slideOver()
            ->schema(fn (SubmissionRequest $record): array => [
                TextInput::make('code_prefix')
                    ->label(__('projects.fields.code_prefix'))
                    ->required()
                    ->maxLength(10)
                    ->default('TAB')
                    ->helperText('Prefijo corto, ej: TAB, CSE, ELEC')
                    ->afterStateUpdated(fn ($set, $state) => $set('code_prefix', strtoupper($state)))
                    ->live(debounce: 500),

                TextInput::make('name')
                    ->label(__('projects.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->default($record->project_name ?? $record->reference_code),

                Select::make('client_id')
                    ->label(__('projects.fields.client'))
                    ->options(Client::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')->label('Razón social')->required(),
                        TextInput::make('contact_name')->label('Contacto'),
                        TextInput::make('email')->label('Email')->email(),
                    ])
                    ->createOptionUsing(function (array $data) {
                        return Client::create(array_merge($data, [
                            'organization_id' => Auth::user()->organization_id,
                        ]))->id;
                    }),

                Select::make('status_id')
                    ->label(__('projects.fields.status'))
                    ->options(fn () => ProjectStatus::orderBy('order')->pluck('name', 'id'))
                    ->preload()
                    ->default(fn () => ProjectStatus::orderBy('order')->value('id'))
                    ->required(),

                Select::make('priority')
                    ->label(__('projects.fields.priority'))
                    ->options(ProjectPriority::class)
                    ->default(ProjectPriority::Media)
                    ->required(),

                DatePicker::make('start_date')
                    ->label(__('projects.fields.start_date'))
                    ->displayFormat('d/m/Y')
                    ->default($record->desired_delivery_date),

                DatePicker::make('end_date')
                    ->label(__('projects.fields.end_date'))
                    ->displayFormat('d/m/Y'),
            ])
            ->action(function (SubmissionRequest $record, array $data): void {
                $project = Project::create(array_merge($data, [
                    'organization_id' => $record->organization_id,
                    'submission_request_id' => $record->id,
                ]));

                Notification::make()
                    ->title(__('projects.notifications.project_created'))
                    ->success()
                    ->send();

                redirect()->route('filament.admin.resources.projects.view', $project);
            });
    }
}
