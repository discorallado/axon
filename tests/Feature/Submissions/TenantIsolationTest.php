<?php

use App\Enums\SubmissionStatus;
use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\SubmissionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('users cannot see submissions from other organizations', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userA = User::factory()->create(['organization_id' => $orgA->id]);

    SubmissionRequest::factory()->for($orgA, 'organization')->create(['status' => SubmissionStatus::Nueva]);
    SubmissionRequest::factory()->for($orgB, 'organization')->create(['status' => SubmissionStatus::Nueva]);

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

it('public form solicitud route is accessible without authentication', function () {
    $this->get(route('solicitud.tableros'))->assertOk();
});
