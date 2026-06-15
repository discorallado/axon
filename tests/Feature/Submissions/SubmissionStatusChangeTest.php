<?php

use App\Enums\SubmissionStatus;
use App\Models\Organization;
use App\Models\SubmissionRequest;
use App\Models\User;
use App\Services\SubmissionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function makeSubmission(Organization $org): SubmissionRequest
{
    return SubmissionRequest::factory()->for($org, 'organization')->create([
        'status' => SubmissionStatus::Nueva,
    ]);
}

it('allows supervisor to advance submission status', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('supervisor', 'web');
    $user->assignRole('supervisor');

    $submission = makeSubmission($org);

    $machine = app(SubmissionStateMachine::class);
    $machine->transition($user, $submission, SubmissionStatus::EnRevision, 'Revisando');

    expect($submission->fresh()->status)->toBe(SubmissionStatus::EnRevision);

    $this->assertDatabaseHas('submission_status_histories', [
        'submission_request_id' => $submission->id,
        'from_status' => SubmissionStatus::Nueva->value,
        'to_status' => SubmissionStatus::EnRevision->value,
        'comment' => 'Revisando',
    ]);
});

it('records status history on each transition', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('ingeniero', 'web');
    $user->assignRole('ingeniero');

    $submission = makeSubmission($org);

    $machine = app(SubmissionStateMachine::class);
    $machine->transition($user, $submission, SubmissionStatus::EnRevision);

    expect($submission->statusHistories()->withoutGlobalScopes()->count())->toBe(1);
});

it('blocks transition from terminal status for non-admin', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('ingeniero', 'web');
    $user->assignRole('ingeniero');

    $submission = makeSubmission($org);
    $submission->update(['status' => SubmissionStatus::Rechazada]);

    $machine = app(SubmissionStateMachine::class);

    $this->expectException(HttpException::class);
    $machine->transition($user, $submission, SubmissionStatus::EnRevision);
});
