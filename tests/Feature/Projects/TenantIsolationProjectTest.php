<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $this->orgA = Organization::factory()->create();
    $this->orgB = Organization::factory()->create();

    $this->userA = User::factory()->create(['organization_id' => $this->orgA->id, 'is_active' => true]);
    $this->userA->assignRole('super_admin');

    $this->userB = User::factory()->create(['organization_id' => $this->orgB->id, 'is_active' => true]);
    $this->userB->assignRole('super_admin');
});

it('users from org A cannot see projects from org B', function () {
    $statusA = ProjectStatus::factory()->create(['organization_id' => $this->orgA->id]);
    $statusB = ProjectStatus::factory()->create(['organization_id' => $this->orgB->id]);
    $clientA = Client::factory()->create(['organization_id' => $this->orgA->id]);
    $clientB = Client::factory()->create(['organization_id' => $this->orgB->id]);

    $this->actingAs($this->userA);
    $projectA = Project::create([
        'organization_id' => $this->orgA->id,
        'client_id' => $clientA->id,
        'status_id' => $statusA->id,
        'code_prefix' => 'AA',
        'name' => 'Proyecto Org A',
        'priority' => 'media',
    ]);

    $this->actingAs($this->userB);
    Project::create([
        'organization_id' => $this->orgB->id,
        'client_id' => $clientB->id,
        'status_id' => $statusB->id,
        'code_prefix' => 'BB',
        'name' => 'Proyecto Org B',
        'priority' => 'media',
    ]);

    $this->actingAs($this->userA);
    $visible = Project::all();

    expect($visible)->toHaveCount(1)
        ->and($visible->first()->id)->toBe($projectA->id);
});

it('tasks inherit organization_id from activity', function () {
    $this->actingAs($this->userA);

    $status = ProjectStatus::factory()->create(['organization_id' => $this->orgA->id]);
    $client = Client::factory()->create(['organization_id' => $this->orgA->id]);

    $project = Project::create([
        'organization_id' => $this->orgA->id,
        'client_id' => $client->id,
        'status_id' => $status->id,
        'code_prefix' => 'ORG',
        'name' => 'Proyecto Test',
        'priority' => 'media',
    ]);

    $activity = Activity::create([
        'organization_id' => $this->orgA->id,
        'project_id' => $project->id,
        'name' => 'Actividad Test',
        'order' => 1,
        'status' => 'pendiente',
    ]);

    $task = Task::create([
        'organization_id' => $this->orgA->id,
        'activity_id' => $activity->id,
        'name' => 'Tarea Test',
        'status' => TaskStatus::Pendiente,
        'priority' => TaskPriority::Media,
    ]);

    expect($task->organization_id)->toBe($this->orgA->id);
});
