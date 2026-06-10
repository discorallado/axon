<?php

use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\SubmissionRequest;
use App\Models\SubmissionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('users cannot see submissions from other organizations', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userA = User::factory()->create(['organization_id' => $orgA->id]);
    $userB = User::factory()->create(['organization_id' => $orgB->id]);

    $templateA = FormTemplate::factory()->for($orgA, 'organization')->create();
    $templateB = FormTemplate::factory()->for($orgB, 'organization')->create();

    $statusA = SubmissionStatus::factory()->initial()->for($orgA, 'organization')->create();
    $statusB = SubmissionStatus::factory()->initial()->for($orgB, 'organization')->create();

    SubmissionRequest::factory()->for($orgA, 'organization')->create([
        'form_template_id' => $templateA->id,
        'status_id' => $statusA->id,
    ]);
    SubmissionRequest::factory()->for($orgB, 'organization')->create([
        'form_template_id' => $templateB->id,
        'status_id' => $statusB->id,
    ]);

    // Autenticado como usuario de orgA, solo debe ver sus solicitudes
    $this->actingAs($userA);

    $visibleIds = SubmissionRequest::pluck('organization_id')->unique()->all();

    expect($visibleIds)->toHaveCount(1)
        ->and($visibleIds[0])->toBe($orgA->id);
});

it('form template global scope filters by organization', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    FormTemplate::factory()->for($orgA, 'organization')->create(['name' => 'Template A']);
    FormTemplate::factory()->for($orgB, 'organization')->create(['name' => 'Template B']);

    $userA = User::factory()->create(['organization_id' => $orgA->id]);
    $this->actingAs($userA);

    $names = FormTemplate::pluck('name')->all();

    expect($names)->toContain('Template A')
        ->and($names)->not->toContain('Template B');
});

it('public form ignores global scope when loading by slug', function () {
    $org = Organization::factory()->create();
    $template = FormTemplate::factory()
        ->for($org, 'organization')
        ->create(['is_active' => true]);

    // Sin usuario autenticado — acceso público
    $response = $this->get(route('public.form.show', $template->slug));

    $response->assertOk();
});
