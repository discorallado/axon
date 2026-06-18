<?php

use App\Livewire\PublicFormWizard;
use App\Models\Organization;
use App\Models\SubmissionItem;
use App\Models\SubmissionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Datos del formulario externo (paso 1 y paso 3) — nivel de proyecto.
 */
function projectFormData(): array
{
    return [
        'project_name' => 'Proyecto Test',
        'installation_location' => 'Santiago',
        'contact_name' => 'Juan Pérez',
        'client_company' => 'Empresa Test SA',
        'contact_email' => 'juan@example.com',
        'contact_phone' => '+56911111111',
        'engineering_by' => 'csenergia',
    ];
}

/**
 * Un ítem de tablero mínimamente válido para agregar a $items.
 */
function minTableroItem(): array
{
    return [
        'label' => 'TG Principal',
        'quantity' => 1,
        'sort_order' => 0,
        'delivery_type' => 'tablero',
        'is_new_installation' => 'nueva',
        'board_type' => 'fuerza',
        'board_function' => 'Distribución principal del edificio',
        'location_type' => 'interior',
        'ip_rating' => 'IP54',
        'ik_rating' => 'IK08',
        'mounting_type' => 'autosoportado',
        'supply_voltage' => '380',
        'electrical_system' => 'trifasico',
        'estimated_power' => 100,
        'power_unit' => 'kW',
        'nominal_current' => 152.0,
        'frequency' => '60',
        'required_protections' => ['interruptor_automatico'],
        'cabinet_material' => 'acero_pintado',
        'special_color' => '7035',
        'ventilation_type' => 'natural',
        'future_expansion' => 'no',
    ];
}

it('creates a submission with valid data', function () {
    Organization::factory()->create();

    $test = Livewire::test(PublicFormWizard::class);

    foreach (projectFormData() as $key => $value) {
        $test->set("data.{$key}", $value);
    }

    $test->set('items', [minTableroItem()]);
    $test->call('submit')->assertSet('submitted', true);

    $submission = SubmissionRequest::withoutGlobalScopes()->first();
    expect($submission)->not->toBeNull()
        ->and($submission->reference_code)->toStartWith('SOL-')
        ->and($submission->submitter_name)->toBe('Juan Pérez')
        ->and($submission->submitter_email)->toBe('juan@example.com')
        ->and($submission->project_name)->toBe('Proyecto Test');
});

it('creates submission_items for each tablero in the list', function () {
    Organization::factory()->create();

    $items = [
        array_merge(minTableroItem(), ['label' => 'TG Principal', 'quantity' => 1, 'sort_order' => 0]),
        array_merge(minTableroItem(), ['label' => 'T-Alumbrado', 'board_type' => 'alumbrado', 'quantity' => 2, 'sort_order' => 1]),
    ];

    $test = Livewire::test(PublicFormWizard::class);
    foreach (projectFormData() as $key => $value) {
        $test->set("data.{$key}", $value);
    }
    $test->set('items', $items);
    $test->call('submit')->assertSet('submitted', true);

    $submission = SubmissionRequest::withoutGlobalScopes()->first();
    $dbItems = SubmissionItem::withoutGlobalScopes()
        ->where('submission_request_id', $submission->id)
        ->orderBy('sort_order')
        ->get();

    expect($dbItems)->toHaveCount(2)
        ->and($dbItems[0]->label)->toBe('TG Principal')
        ->and($dbItems[0]->quantity)->toBe(1)
        ->and($dbItems[1]->label)->toBe('T-Alumbrado')
        ->and($dbItems[1]->quantity)->toBe(2)
        ->and($dbItems[1]->board_type)->toBe('alumbrado');
});

it('blocks submit without items and does not create a submission', function () {
    Organization::factory()->create();

    $test = Livewire::test(PublicFormWizard::class);
    foreach (projectFormData() as $key => $value) {
        $test->set("data.{$key}", $value);
    }
    // No items added
    $test->call('submit')->assertSet('submitted', false);

    expect(SubmissionRequest::withoutGlobalScopes()->count())->toBe(0);
});

it('stores project-level fields on submission_requests', function () {
    Organization::factory()->create();

    $test = Livewire::test(PublicFormWizard::class);
    $test->set('data', array_merge(projectFormData(), [
        'cost_center' => 'CC-001',
        'desired_delivery_date' => '2026-09-01',
    ]));
    $test->set('items', [minTableroItem()]);
    $test->call('submit');

    $submission = SubmissionRequest::withoutGlobalScopes()->first();
    expect($submission->cost_center)->toBe('CC-001')
        ->and($submission->engineering_by)->toBe('csenergia')
        ->and($submission->installation_location)->toBe('Santiago');
});
