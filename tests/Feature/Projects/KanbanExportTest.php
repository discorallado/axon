<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Exports\TasksExport;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $this->org = Organization::factory()->create();
    $this->user = User::factory()->create(['organization_id' => $this->org->id, 'is_active' => true]);
    $this->user->assignRole('super_admin');

    $status = ProjectStatus::factory()->create(['organization_id' => $this->org->id]);
    $client = Client::factory()->create(['organization_id' => $this->org->id]);

    $this->actingAs($this->user);

    $this->project = Project::create([
        'organization_id' => $this->org->id,
        'client_id' => $client->id,
        'status_id' => $status->id,
        'code_prefix' => 'KAN',
        'name' => 'Proyecto Kanban Test',
        'priority' => 'media',
    ]);

    $this->activity = Activity::create([
        'organization_id' => $this->org->id,
        'project_id' => $this->project->id,
        'name' => 'Actividad Test',
        'order' => 1,
        'status' => 'pendiente',
    ]);
});

it('updates task status when moved to a different column', function () {
    $task = Task::create([
        'organization_id' => $this->org->id,
        'activity_id' => $this->activity->id,
        'name' => 'Tarea de prueba',
        'status' => TaskStatus::Pendiente,
        'priority' => TaskPriority::Media,
    ]);

    expect($task->status)->toBe(TaskStatus::Pendiente);

    $task->update(['status' => TaskStatus::EnProgreso]);

    expect($task->fresh()->status)->toBe(TaskStatus::EnProgreso);
});

it('assigns a position when moving a task between columns', function () {
    $task = Task::create([
        'organization_id' => $this->org->id,
        'activity_id' => $this->activity->id,
        'name' => 'Tarea con posición',
        'status' => TaskStatus::Pendiente,
        'priority' => TaskPriority::Media,
    ]);

    $task->update([
        'status' => TaskStatus::EnProgreso,
        'position' => '1000.0',
    ]);

    expect($task->fresh()->status)->toBe(TaskStatus::EnProgreso)
        ->and($task->fresh()->position)->toBe('1000.0');
});

it('generates a valid xlsx export with correct headings', function () {
    Task::create([
        'organization_id' => $this->org->id,
        'activity_id' => $this->activity->id,
        'name' => 'Tarea exportable',
        'status' => TaskStatus::Pendiente,
        'priority' => TaskPriority::Alta,
        'start_date' => now()->subDays(3),
        'due_date' => now()->addDays(7),
        'estimated_hours' => 8.0,
    ]);

    $export = new TasksExport($this->project->id);

    $collection = $export->query()->get();
    expect($collection)->toHaveCount(1);

    $headings = $export->headings();
    expect($headings)->toHaveCount(10)
        ->and($headings[0])->toBe('Código')
        ->and($headings[1])->toBe('Nombre');

    $row = $export->map($collection->first());
    expect($row[0])->not->toBeEmpty()
        ->and($row[1])->toBe('Tarea exportable')
        ->and($row[2])->toBe('Actividad Test');
});

it('export only includes tasks from the given project', function () {
    $otherProject = Project::create([
        'organization_id' => $this->org->id,
        'client_id' => $this->project->client_id,
        'status_id' => $this->project->status_id,
        'code_prefix' => 'OTR',
        'name' => 'Otro Proyecto',
        'priority' => 'media',
    ]);

    $otherActivity = Activity::create([
        'organization_id' => $this->org->id,
        'project_id' => $otherProject->id,
        'name' => 'Actividad Otro',
        'order' => 1,
        'status' => 'pendiente',
    ]);

    Task::create([
        'organization_id' => $this->org->id,
        'activity_id' => $this->activity->id,
        'name' => 'Tarea del proyecto correcto',
        'status' => TaskStatus::Pendiente,
        'priority' => TaskPriority::Media,
    ]);

    Task::create([
        'organization_id' => $this->org->id,
        'activity_id' => $otherActivity->id,
        'name' => 'Tarea de otro proyecto',
        'status' => TaskStatus::Pendiente,
        'priority' => TaskPriority::Media,
    ]);

    $export = new TasksExport($this->project->id);
    $collection = $export->query()->get();

    expect($collection)->toHaveCount(1)
        ->and($collection->first()->name)->toBe('Tarea del proyecto correcto');
});
