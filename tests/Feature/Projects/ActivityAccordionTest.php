<?php

use App\Enums\ActivityStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Livewire\ActivityAccordion;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\User;
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
        'name' => 'Instalación eléctrica',
        'order' => 1,
    ]);

    $this->task = Task::factory()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $this->activity->id,
        'status' => TaskStatus::Pendiente,
        'priority' => TaskPriority::Media,
    ]);
});

it('renders the activity accordion for a project', function () {
    $this->actingAs($this->admin);

    livewire(ActivityAccordion::class, ['projectId' => $this->project->id])
        ->assertSee($this->activity->name)
        ->assertSee($this->activity->status->getLabel());
});

it('shows the empty state when no activities exist', function () {
    $this->actingAs($this->admin);

    $emptyProject = Project::factory()->create([
        'organization_id' => $this->org->id,
        'client_id' => $this->client->id,
        'status_id' => $this->status->id,
    ]);

    livewire(ActivityAccordion::class, ['projectId' => $emptyProject->id])
        ->assertSee(__('tasks.activities.empty'));
});

// Section collapse is handled by Alpine (frontend-only); task rows are always in the DOM.
it('renders task data in the schema', function () {
    $this->actingAs($this->admin);

    livewire(ActivityAccordion::class, ['projectId' => $this->project->id])
        ->assertSee($this->task->name)
        ->assertSee($this->task->code)
        ->assertSee($this->task->status->getLabel());
});

it('creates a new activity', function () {
    $this->actingAs($this->admin);

    $emptyProject = Project::factory()->create([
        'organization_id' => $this->org->id,
        'client_id' => $this->client->id,
        'status_id' => $this->status->id,
    ]);

    livewire(ActivityAccordion::class, ['projectId' => $emptyProject->id])
        ->callAction('createActivity', data: [
            'name' => 'Nueva actividad de prueba',
            'order' => 1,
        ])
        ->assertHasNoActionErrors();

    expect(Activity::where('name', 'Nueva actividad de prueba')->exists())->toBeTrue();
});

it('edits an existing activity', function () {
    $this->actingAs($this->admin);

    livewire(ActivityAccordion::class, ['projectId' => $this->project->id])
        ->callAction('editActivity', data: [
            'name' => 'Instalación actualizada',
            'order' => 1,
        ], arguments: ['activityId' => $this->activity->id])
        ->assertHasNoActionErrors();

    expect($this->activity->fresh()->name)->toBe('Instalación actualizada');
});

it('computed status is Pendiente when all tasks are pending', function () {
    // La tarea del beforeEach está en Pendiente → actividad debe ser Pendiente
    expect($this->activity->load('tasks')->status)->toBe(ActivityStatus::Pendiente);
});

it('computed status is EnProgreso when a task is active', function () {
    $this->task->update(['status' => TaskStatus::EnProgreso]);

    expect($this->activity->load('tasks')->status)->toBe(ActivityStatus::EnProgreso);
});

it('computed status is Completada when all tasks are completed', function () {
    $this->task->update(['status' => TaskStatus::Completada]);

    expect($this->activity->load('tasks')->status)->toBe(ActivityStatus::Completada);
});

it('deletes an activity', function () {
    $this->actingAs($this->admin);

    livewire(ActivityAccordion::class, ['projectId' => $this->project->id])
        ->callAction('deleteActivity', arguments: ['activityId' => $this->activity->id])
        ->assertHasNoActionErrors();

    expect(Activity::find($this->activity->id))->toBeNull();
});

it('creates a task for an activity', function () {
    $this->actingAs($this->admin);

    livewire(ActivityAccordion::class, ['projectId' => $this->project->id])
        ->callAction('createTask', data: [
            'name' => 'Tarea creada desde acordeón',
            'status' => TaskStatus::Pendiente->value,
            'priority' => TaskPriority::Alta->value,
        ], arguments: ['activityId' => $this->activity->id])
        ->assertHasNoActionErrors();

    expect(Task::where('name', 'Tarea creada desde acordeón')->exists())->toBeTrue();
});

it('edits a task', function () {
    $this->actingAs($this->admin);

    livewire(ActivityAccordion::class, ['projectId' => $this->project->id])
        ->callAction('editTask', data: [
            'name' => 'Tarea editada',
            'status' => TaskStatus::EnProgreso->value,
            'priority' => TaskPriority::Alta->value,
        ], arguments: ['taskId' => $this->task->id])
        ->assertHasNoActionErrors();

    expect($this->task->fresh()->name)->toBe('Tarea editada')
        ->and($this->task->fresh()->status)->toBe(TaskStatus::EnProgreso);
});

it('deletes a task', function () {
    $this->actingAs($this->admin);

    livewire(ActivityAccordion::class, ['projectId' => $this->project->id])
        ->callAction('deleteTask', arguments: ['taskId' => $this->task->id])
        ->assertHasNoActionErrors();

    expect(Task::find($this->task->id))->toBeNull();
});

it('renders overdue task due date in the schema', function () {
    $this->actingAs($this->admin);

    $dueDate = now()->subDays(3);
    $this->task->update([
        'due_date' => $dueDate,
        'status' => TaskStatus::Pendiente,
    ]);

    livewire(ActivityAccordion::class, ['projectId' => $this->project->id])
        ->assertSee($dueDate->format('d/m/Y'))
        ->assertSee($this->task->status->getLabel());
});

it('respects organization_id on created activity', function () {
    $this->actingAs($this->admin);

    $emptyProject = Project::factory()->create([
        'organization_id' => $this->org->id,
        'client_id' => $this->client->id,
        'status_id' => $this->status->id,
    ]);

    livewire(ActivityAccordion::class, ['projectId' => $emptyProject->id])
        ->callAction('createActivity', data: [
            'name' => 'Actividad con org',
            'order' => 1,
        ]);

    $created = Activity::where('name', 'Actividad con org')->first();
    expect($created->organization_id)->toBe($this->org->id);
});

it('respects organization_id on created task', function () {
    $this->actingAs($this->admin);

    livewire(ActivityAccordion::class, ['projectId' => $this->project->id])
        ->callAction('createTask', data: [
            'name' => 'Tarea con org',
            'status' => TaskStatus::Pendiente->value,
            'priority' => TaskPriority::Media->value,
        ], arguments: ['activityId' => $this->activity->id]);

    $created = Task::where('name', 'Tarea con org')->first();
    expect($created->organization_id)->toBe($this->org->id);
});
