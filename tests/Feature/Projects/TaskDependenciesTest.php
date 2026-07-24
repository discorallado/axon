<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Livewire\ActivityAccordion;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\TaskLink;
use App\Models\User;
use App\Services\TaskDependencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->org = Organization::factory()->create();

    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create([
        'organization_id' => $this->org->id,
        'is_active' => true,
    ]);
    $this->admin->assignRole('super_admin');

    $this->client = Client::factory()->create(['organization_id' => $this->org->id]);
    $this->status = ProjectStatus::factory()->create(['organization_id' => $this->org->id]);

    $this->project = Project::factory()->create([
        'organization_id' => $this->org->id,
        'client_id' => $this->client->id,
        'status_id' => $this->status->id,
    ]);

    $this->activity = Activity::factory()->create([
        'organization_id' => $this->org->id,
        'project_id' => $this->project->id,
        'order' => 1,
    ]);

    $this->taskA = Task::factory()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $this->activity->id,
    ]);

    $this->taskB = Task::factory()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $this->activity->id,
    ]);

    $this->taskC = Task::factory()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $this->activity->id,
    ]);
});

// ─── TaskDependencyService ─────────────────────────────────────────────────────

it('creates a task_links row for each new predecessor', function () {
    $skipped = TaskDependencyService::syncPredecessors($this->taskB, $this->project->id, [$this->taskA->id]);

    expect($skipped)->toBeEmpty();

    expect(TaskLink::where('target_id', $this->taskB->id)->where('source_id', $this->taskA->id)->exists())->toBeTrue();
    expect($this->taskB->predecessors()->pluck('tasks.id')->all())->toBe([$this->taskA->id]);
});

it('removes predecessors that are no longer selected', function () {
    TaskDependencyService::syncPredecessors($this->taskB, $this->project->id, [$this->taskA->id]);

    TaskDependencyService::syncPredecessors($this->taskB, $this->project->id, []);

    expect(TaskLink::where('target_id', $this->taskB->id)->exists())->toBeFalse();
});

it('preserves the dependency type of links that stay selected', function () {
    TaskLink::create([
        'organization_id' => $this->org->id,
        'project_id' => $this->project->id,
        'source_id' => $this->taskA->id,
        'target_id' => $this->taskB->id,
        'type' => 1, // SS, definido desde el Gantt
    ]);

    TaskDependencyService::syncPredecessors($this->taskB, $this->project->id, [$this->taskA->id]);

    $link = TaskLink::where('target_id', $this->taskB->id)->where('source_id', $this->taskA->id)->first();
    expect($link->type)->toBe(1);
});

it('ignores the task itself as a predecessor', function () {
    $skipped = TaskDependencyService::syncPredecessors($this->taskA, $this->project->id, [$this->taskA->id]);

    expect(TaskLink::count())->toBe(0);
});

it('detects a direct cycle between two tasks', function () {
    TaskDependencyService::syncPredecessors($this->taskB, $this->project->id, [$this->taskA->id]);

    $skipped = TaskDependencyService::syncPredecessors($this->taskA, $this->project->id, [$this->taskB->id]);

    expect($skipped)->toBe([$this->taskB->id]);
    expect(TaskLink::where('source_id', $this->taskB->id)->where('target_id', $this->taskA->id)->exists())->toBeFalse();
});

it('detects a transitive cycle across three tasks', function () {
    // A -> B -> C  (A precede a B, B precede a C)
    TaskDependencyService::syncPredecessors($this->taskB, $this->project->id, [$this->taskA->id]);
    TaskDependencyService::syncPredecessors($this->taskC, $this->project->id, [$this->taskB->id]);

    // Intentar que A dependa de C cerraría el ciclo A -> B -> C -> A
    $skipped = TaskDependencyService::syncPredecessors($this->taskA, $this->project->id, [$this->taskC->id]);

    expect($skipped)->toBe([$this->taskC->id]);
    expect(TaskLink::where('source_id', $this->taskC->id)->where('target_id', $this->taskA->id)->exists())->toBeFalse();
});

// ─── ActivityAccordion (Livewire) ──────────────────────────────────────────────

it('creates a task with predecessors from the activity accordion form', function () {
    $this->actingAs($this->admin);

    livewire(ActivityAccordion::class, ['projectId' => $this->project->id])
        ->callAction('createTask', data: [
            'name' => 'Tarea con dependencia',
            'status' => TaskStatus::Pendiente->value,
            'priority' => TaskPriority::Media->value,
            'predecessors' => [$this->taskA->id],
        ], arguments: ['activityId' => $this->activity->id]);

    $created = Task::where('name', 'Tarea con dependencia')->firstOrFail();

    expect($created->predecessors()->pluck('tasks.id')->all())->toBe([$this->taskA->id]);
});

it('lets a freshly created task be selected as a predecessor right away', function () {
    $this->actingAs($this->admin);

    livewire(ActivityAccordion::class, ['projectId' => $this->project->id])
        ->callAction('createTask', data: [
            'name' => 'Tarea recién creada',
            'status' => TaskStatus::Pendiente->value,
            'priority' => TaskPriority::Media->value,
        ], arguments: ['activityId' => $this->activity->id]);

    $freshTask = Task::where('name', 'Tarea recién creada')->firstOrFail();

    livewire(ActivityAccordion::class, ['projectId' => $this->project->id])
        ->callAction('editTask', data: [
            'name' => $this->taskA->name,
            'status' => $this->taskA->status->value,
            'priority' => $this->taskA->priority->value,
            'predecessors' => [$freshTask->id],
        ], arguments: ['taskId' => $this->taskA->id]);

    expect($this->taskA->fresh()->predecessors()->pluck('tasks.id')->all())->toBe([$freshTask->id]);
});
