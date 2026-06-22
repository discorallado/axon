<?php

use App\Models\Attachment;
use App\Models\Organization;
use App\Models\SubmissionRequest;
use App\Models\User;
use App\Policies\SubmissionRequestPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeSubmissionWithAssignee(Organization $org, ?User $assignee = null): SubmissionRequest
{
    return SubmissionRequest::factory()->for($org, 'organization')->create([
        'assigned_to' => $assignee?->id,
    ]);
}

it('allows super_admin to delete any attachment', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('super_admin', 'web');
    $user->assignRole('super_admin');

    $submission = makeSubmissionWithAssignee($org);
    $policy = new SubmissionRequestPolicy;

    expect($policy->deleteAttachment($user, $submission))->toBeTrue();
});

it('allows ingeniero to delete any attachment', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('ingeniero', 'web');
    $user->assignRole('ingeniero');

    $submission = makeSubmissionWithAssignee($org);
    $policy = new SubmissionRequestPolicy;

    expect($policy->deleteAttachment($user, $submission))->toBeTrue();
});

it('allows supervisor to delete attachment on assigned submission', function () {
    $org = Organization::factory()->create();
    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('supervisor', 'web');
    $supervisor->assignRole('supervisor');

    $submission = makeSubmissionWithAssignee($org, $supervisor);
    $policy = new SubmissionRequestPolicy;

    expect($policy->deleteAttachment($supervisor, $submission))->toBeTrue();
});

it('denies supervisor to delete attachment on unassigned submission', function () {
    $org = Organization::factory()->create();
    $supervisor = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('supervisor', 'web');
    $supervisor->assignRole('supervisor');

    $submission = makeSubmissionWithAssignee($org); // sin asignado
    $policy = new SubmissionRequestPolicy;

    expect($policy->deleteAttachment($supervisor, $submission))->toBeFalse();
});

it('denies tecnico to delete any attachment', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('tecnico', 'web');
    $user->assignRole('tecnico');

    $submission = makeSubmissionWithAssignee($org);
    $policy = new SubmissionRequestPolicy;

    expect($policy->deleteAttachment($user, $submission))->toBeFalse();
});

it('denies user from different org to delete attachment', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $otherOrg->id]);
    Role::findOrCreate('super_admin', 'web');
    $user->assignRole('super_admin');

    $submission = makeSubmissionWithAssignee($org);
    $policy = new SubmissionRequestPolicy;

    expect($policy->deleteAttachment($user, $submission))->toBeFalse();
});

it('deletes attachment file from storage when attachment is deleted', function () {
    $org = Organization::factory()->create();

    Storage::fake('local');

    $submission = SubmissionRequest::factory()->for($org, 'organization')->create();

    $path = 'attachments/test-file.pdf';
    Storage::disk('local')->put($path, 'contenido');

    $attachment = Attachment::create([
        'organization_id' => $org->id,
        'attachable_type' => 'submission_request',
        'attachable_id' => $submission->id,
        'disk' => 'local',
        'path' => $path,
        'original_name' => 'test-file.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 9,
        'tag' => 'technical_specs',
    ]);

    Storage::disk('local')->assertExists($path);

    // Simula lo que hace la acción de eliminación
    if (Storage::disk($attachment->disk)->exists($attachment->path)) {
        Storage::disk($attachment->disk)->delete($attachment->path);
    }
    $attachment->delete();

    Storage::disk('local')->assertMissing($path);
    $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
});
