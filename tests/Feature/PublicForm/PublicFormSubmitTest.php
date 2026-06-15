<?php

use App\Livewire\PublicFormWizard;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\SubmissionRequest;
use App\Models\SubmissionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeActiveTemplate(Organization $org): void
{
    FormTemplate::factory()->for($org, 'organization')->create([
        'slug' => 'tableros-electricos',
        'is_active' => true,
    ]);
    SubmissionStatus::factory()->initial()->for($org, 'organization')->create();
}

it('creates a submission with valid data', function () {
    Notification::fake();

    $org = Organization::factory()->create();
    makeActiveTemplate($org);

    Livewire::test(PublicFormWizard::class)
        ->set('data.submitter_name', 'Juan Pérez')
        ->set('data.submitter_email', 'juan@example.com')
        ->set('data.project_type', 'data_center')
        ->set('data.project_location', 'Santiago, RM')
        ->set('data.board_type', 'distribucion')
        ->set('data.nominal_voltage', '380V')
        ->set('data.nominal_current', '100')
        ->call('submit')
        ->assertSet('submitted', true);

    $this->assertDatabaseHas('submission_requests', [
        'submitter_email' => 'juan@example.com',
    ]);

    $submission = SubmissionRequest::withoutGlobalScopes()->first();
    expect($submission->reference_code)->toStartWith('SOL-');
});

it('requires submitter name and email', function () {
    $org = Organization::factory()->create();
    makeActiveTemplate($org);

    Livewire::test(PublicFormWizard::class)
        ->set('data.project_type', 'data_center')
        ->call('submit')
        ->assertHasErrors(['data.submitter_name', 'data.submitter_email']);
});

it('saves answers for filled optional fields', function () {
    Notification::fake();

    $org = Organization::factory()->create();
    makeActiveTemplate($org);

    Livewire::test(PublicFormWizard::class)
        ->set('data.submitter_name', 'Ana Torres')
        ->set('data.submitter_email', 'ana@example.com')
        ->set('data.project_type', 'industrial')
        ->set('data.project_location', 'Antofagasta')
        ->set('data.board_type', 'control')
        ->set('data.nominal_voltage', '220V')
        ->set('data.nominal_current', '50')
        ->set('data.specs_notes', 'Con variador de frecuencia')
        ->call('submit');

    $submission = SubmissionRequest::withoutGlobalScopes()->first();
    expect($submission->answers()->withoutGlobalScopes()->where('question_key', 'specs_notes')->exists())
        ->toBeTrue();
});

it('skips empty optional fields and does not create answers for them', function () {
    Notification::fake();

    $org = Organization::factory()->create();
    makeActiveTemplate($org);

    Livewire::test(PublicFormWizard::class)
        ->set('data.submitter_name', 'Ana Torres')
        ->set('data.submitter_email', 'ana@example.com')
        ->set('data.project_type', 'industrial')
        ->set('data.project_location', 'Antofagasta')
        ->set('data.board_type', 'control')
        ->set('data.nominal_voltage', '220V')
        ->set('data.nominal_current', '50')
        ->call('submit');

    $submission = SubmissionRequest::withoutGlobalScopes()->first();
    expect($submission->answers()->withoutGlobalScopes()->where('question_key', 'specs_notes')->exists())
        ->toBeFalse();
});
