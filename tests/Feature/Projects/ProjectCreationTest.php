<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Enums\ProjectPriority;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\User;
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

    $this->client = Client::factory()->create(['organization_id' => $this->org->id]);

    $this->status = ProjectStatus::factory()->create([
        'organization_id' => $this->org->id,
        'name' => 'Planificación',
        'order' => 1,
    ]);
});

it('generates a readable project code on creation', function () {
    $this->actingAs($this->admin);

    $project = Project::create([
        'organization_id' => $this->org->id,
        'client_id' => $this->client->id,
        'status_id' => $this->status->id,
        'code_prefix' => 'TAB',
        'name' => 'Tablero CSE-001',
        'priority' => ProjectPriority::Media,
    ]);

    expect($project->code)->toStartWith('TAB-'.now()->year.'-');
});

it('increments project code sequence within the same prefix', function () {
    $this->actingAs($this->admin);

    $p1 = Project::create([
        'organization_id' => $this->org->id,
        'client_id' => $this->client->id,
        'status_id' => $this->status->id,
        'code_prefix' => 'CSE',
        'name' => 'Proyecto 1',
        'priority' => ProjectPriority::Media,
    ]);

    $p2 = Project::create([
        'organization_id' => $this->org->id,
        'client_id' => $this->client->id,
        'status_id' => $this->status->id,
        'code_prefix' => 'CSE',
        'name' => 'Proyecto 2',
        'priority' => ProjectPriority::Media,
    ]);

    $seq1 = (int) substr($p1->code, strrpos($p1->code, '-') + 1);
    $seq2 = (int) substr($p2->code, strrpos($p2->code, '-') + 1);

    expect($seq2)->toBe($seq1 + 1);
});

it('creates the hierarchy project → activity → task', function () {
    $this->actingAs($this->admin);

    $project = Project::factory()->create([
        'organization_id' => $this->org->id,
        'client_id' => $this->client->id,
        'status_id' => $this->status->id,
    ]);

    $activity = Activity::create([
        'organization_id' => $this->org->id,
        'project_id' => $project->id,
        'name' => 'Instalación eléctrica',
        'order' => 1,
        'status' => 'pendiente',
    ]);

    $task = Task::create([
        'organization_id' => $this->org->id,
        'activity_id' => $activity->id,
        'name' => 'Tender cableado',
        'status' => TaskStatus::Pendiente,
        'priority' => TaskPriority::Media,
    ]);

    expect($project->activities)->toHaveCount(1)
        ->and($activity->tasks)->toHaveCount(1)
        ->and($task->code)->not->toBeEmpty();
});

it('calculates completion percentage correctly', function () {
    $this->actingAs($this->admin);

    $project = Project::factory()->create([
        'organization_id' => $this->org->id,
        'client_id' => $this->client->id,
        'status_id' => $this->status->id,
    ]);

    $activity = Activity::factory()->create([
        'organization_id' => $this->org->id,
        'project_id' => $project->id,
    ]);

    Task::factory()->count(3)->create([
        'organization_id' => $this->org->id,
        'activity_id' => $activity->id,
    ]);

    Task::factory()->completed()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $activity->id,
    ]);

    expect($project->fresh()->completionPercentage())->toBe(25.0);
});

it('detects overdue tasks', function () {
    $this->actingAs($this->admin);

    $activity = Activity::factory()->create(['organization_id' => $this->org->id]);

    $overdueTask = Task::factory()->overdue()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $activity->id,
    ]);

    $normalTask = Task::factory()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $activity->id,
        'due_date' => now()->addDays(5),
    ]);

    expect($overdueTask->isOverdue())->toBeTrue()
        ->and($normalTask->isOverdue())->toBeFalse();
});
