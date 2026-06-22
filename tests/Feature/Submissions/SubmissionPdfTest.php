<?php

use App\Enums\SubmissionStatus;
use App\Models\Organization;
use App\Models\SubmissionItem;
use App\Models\SubmissionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('returns pdf for authenticated user of the same org', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('supervisor', 'web');
    $user->assignRole('supervisor');

    $submission = SubmissionRequest::factory()->for($org, 'organization')->create([
        'status' => SubmissionStatus::Nueva,
    ]);
    SubmissionItem::factory()->for($submission, 'submissionRequest')->for($org, 'organization')->create();

    $response = $this->actingAs($user)->get(route('submissions.pdf', $submission));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
});

it('returns 404 for user from a different org due to tenant scope', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $user = User::factory()->create(['organization_id' => $orgB->id]);
    Role::findOrCreate('supervisor', 'web');
    $user->assignRole('supervisor');

    $submission = SubmissionRequest::factory()->for($orgA, 'organization')->create([
        'status' => SubmissionStatus::Nueva,
    ]);

    // El global scope de tenant filtra la solicitud antes del controller → 404
    $this->actingAs($user)->get(route('submissions.pdf', $submission))
        ->assertNotFound();
});

it('redirects guest to filament login', function () {
    $org = Organization::factory()->create();
    $submission = SubmissionRequest::factory()->for($org, 'organization')->create();

    $this->get(route('submissions.pdf', $submission))
        ->assertRedirectToRoute('filament.admin.auth.login');
});
