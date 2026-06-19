<?php

use App\Enums\SubmissionStatus;
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

it('users from org B cannot see submissions from org A', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $userB = User::factory()->create(['organization_id' => $orgB->id]);

    SubmissionRequest::factory()->for($orgA, 'organization')->create(['status' => SubmissionStatus::Nueva]);
    SubmissionRequest::factory()->for($orgB, 'organization')->create(['status' => SubmissionStatus::Nueva]);

    $this->actingAs($userB);

    $visibleIds = SubmissionRequest::pluck('organization_id')->unique()->all();

    expect($visibleIds)->toHaveCount(1)
        ->and($visibleIds[0])->toBe($orgB->id);
});

it('public form solicitud route is accessible without authentication', function () {
    $this->get(route('solicitud.tableros'))->assertOk();
});
