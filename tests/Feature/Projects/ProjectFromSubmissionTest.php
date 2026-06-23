<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Enums\SubmissionStatus;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\SubmissionRequest;
use App\Models\User;
use App\Notifications\SubmissionApprovedNotification;
use App\Services\SubmissionStateMachine;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->org = Organization::factory()->create();
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'ingeniero', 'guard_name' => 'web']);

    $this->admin = User::factory()->create([
        'organization_id' => $this->org->id,
        'is_active' => true,
    ]);
    $this->admin->assignRole('super_admin');

    ProjectStatus::factory()->create([
        'organization_id' => $this->org->id,
        'name' => 'Planificación',
        'order' => 1,
    ]);
});

it('sends SubmissionApprovedNotification when status transitions to aprobada', function () {
    Notification::fake();
    $this->actingAs($this->admin);

    $submission = SubmissionRequest::factory()->create([
        'organization_id' => $this->org->id,
        'status' => SubmissionStatus::Cotizada,
    ]);

    $machine = app(SubmissionStateMachine::class);
    $machine->transition($this->admin, $submission, SubmissionStatus::Aprobada);

    Notification::assertSentTo($this->admin, SubmissionApprovedNotification::class);
});

it('does not send notification when transitioning to non-aprobada status', function () {
    Notification::fake();
    $this->actingAs($this->admin);

    $submission = SubmissionRequest::factory()->create([
        'organization_id' => $this->org->id,
        'status' => SubmissionStatus::Nueva,
    ]);

    $machine = app(SubmissionStateMachine::class);
    $machine->transition($this->admin, $submission, SubmissionStatus::EnRevision);

    Notification::assertNothingSent();
});

it('creates a project linked to the submission request', function () {
    $this->actingAs($this->admin);

    $submission = SubmissionRequest::factory()->create([
        'organization_id' => $this->org->id,
        'status' => SubmissionStatus::Aprobada,
        'project_name' => 'Tablero Principal',
    ]);

    $status = ProjectStatus::where('organization_id', $this->org->id)->first();
    $client = Client::factory()->create(['organization_id' => $this->org->id]);

    $project = Project::create([
        'organization_id' => $this->org->id,
        'client_id' => $client->id,
        'status_id' => $status->id,
        'submission_request_id' => $submission->id,
        'code_prefix' => 'TAB',
        'name' => $submission->project_name,
        'priority' => 'media',
    ]);

    expect($project->submissionRequest->id)->toBe($submission->id)
        ->and($submission->fresh()->project->id)->toBe($project->id);
});
