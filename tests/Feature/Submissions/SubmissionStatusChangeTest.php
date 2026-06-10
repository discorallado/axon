<?php

use App\Models\FormTemplate;
use App\Models\Organization;
use App\Models\SubmissionRequest;
use App\Models\SubmissionStatus;
use App\Models\User;
use App\Services\SubmissionStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

function makeSubmissionWithStatuses(Organization $org): array
{
    $template = FormTemplate::factory()->for($org, 'organization')->create();

    $inicial = SubmissionStatus::factory()->initial()->for($org, 'organization')->create();
    $revision = SubmissionStatus::factory()->for($org, 'organization')
        ->create(['slug' => 'en_revision', 'name' => 'En revisión', 'sort_order' => 2]);
    $terminal = SubmissionStatus::factory()->terminal()->for($org, 'organization')
        ->create(['slug' => 'rechazada', 'name' => 'Rechazada', 'sort_order' => 5]);

    $submission = SubmissionRequest::factory()
        ->for($org, 'organization')
        ->create([
            'form_template_id' => $template->id,
            'status_id' => $inicial->id,
        ]);

    return [$submission, $inicial, $revision, $terminal];
}

it('allows supervisor to advance submission status', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('supervisor', 'web');
    $user->assignRole('supervisor');

    [$submission, $inicial, $revision] = makeSubmissionWithStatuses($org);

    $machine = app(SubmissionStateMachine::class);
    $machine->transition($user, $submission, $revision, 'Revisando');

    expect($submission->fresh()->status_id)->toBe($revision->id);

    $this->assertDatabaseHas('submission_status_histories', [
        'submission_request_id' => $submission->id,
        'from_status_id' => $inicial->id,
        'to_status_id' => $revision->id,
        'comment' => 'Revisando',
    ]);
});

it('records status history on each transition', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('ingeniero', 'web');
    $user->assignRole('ingeniero');

    [$submission, $inicial, $revision] = makeSubmissionWithStatuses($org);

    $machine = app(SubmissionStateMachine::class);
    $machine->transition($user, $submission, $revision);

    expect($submission->statusHistories()->withoutGlobalScopes()->count())->toBe(1);
});

it('blocks transition from terminal status for non-admin', function () {
    $org = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $org->id]);
    Role::findOrCreate('ingeniero', 'web');
    $user->assignRole('ingeniero');

    [$submission, $inicial, $revision, $terminal] = makeSubmissionWithStatuses($org);

    // Poner en estado terminal
    $submission->update(['status_id' => $terminal->id]);

    $machine = app(SubmissionStateMachine::class);

    $this->expectException(HttpException::class);
    $machine->transition($user, $submission, $revision);
});
