<?php

use App\Livewire\PublicFormWizard;
use App\Models\Organization;
use App\Models\SubmissionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Estado mínimo válido que satisface todos los campos required del wizard.
 */
function minValidState(): array
{
    return [
        // 1. General
        'project_name' => 'Proyecto Test',
        'client_name' => 'Cliente Test',
        'installation_location' => 'Santiago',
        'delivery_type' => 'tablero',
        'is_new_installation' => 'nueva',
        'engineering_by' => 'nuestra_empresa',
        'contact_name' => 'Juan Pérez',
        'contact_email' => 'juan@example.com',
        // 2. Función y Alcance
        'board_type' => ['fuerza'],
        'board_function' => 'Distribución principal del edificio',
        // 3. Eléctrico
        'supply_voltage' => '380V',
        'frequency' => '60hz',
        'electrical_system' => 'trifasico',
        'grounding_system' => 'tn_s',
        'required_protections' => ['interruptor_automatico'],
        // 4. Instalación
        'installation_environment' => ['interior'],
        'ip_rating' => 'IP54',
        // 5. Diseño
        'cabinet_material' => 'acero_pintado',
        'mounting_type' => 'autosoportado',
        'ventilation_type' => 'natural',
        'future_expansion' => 'no',
        // 6. Normativa
        'applicable_normative' => ['ric_n2'],
    ];
}

it('creates a submission with valid data', function () {
    Notification::fake();
    Organization::factory()->create();

    $test = Livewire::test(PublicFormWizard::class);
    foreach (minValidState() as $key => $value) {
        $test->set("data.{$key}", $value);
    }
    $test->call('submit')->assertSet('submitted', true);

    $submission = SubmissionRequest::withoutGlobalScopes()->first();
    expect($submission)->not->toBeNull()
        ->and($submission->reference_code)->toStartWith('SOL-')
        ->and($submission->submitter_name)->toBe('Juan Pérez')
        ->and($submission->submitter_email)->toBe('juan@example.com');
});

it('saves answers for filled optional fields', function () {
    Notification::fake();
    Organization::factory()->create();

    $state = array_merge(minValidState(), ['additional_observations' => 'Con variador de frecuencia']);
    $test = Livewire::test(PublicFormWizard::class);
    foreach ($state as $key => $value) {
        $test->set("data.{$key}", $value);
    }
    $test->call('submit');

    $submission = SubmissionRequest::withoutGlobalScopes()->first();
    expect(
        $submission->answers()->withoutGlobalScopes()
            ->where('question_key', 'additional_observations')
            ->exists()
    )->toBeTrue();
});

it('skips empty optional fields and does not create answers for them', function () {
    Notification::fake();
    Organization::factory()->create();

    $test = Livewire::test(PublicFormWizard::class);
    foreach (minValidState() as $key => $value) {
        $test->set("data.{$key}", $value);
    }
    $test->call('submit');

    $submission = SubmissionRequest::withoutGlobalScopes()->first();
    expect(
        $submission->answers()->withoutGlobalScopes()
            ->where('question_key', 'additional_observations')
            ->exists()
    )->toBeFalse();
});
