<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Exports\TasksExport;
use App\Filament\Resources\ProjectResource\Pages\GanttChart;
use App\Filament\Resources\ProjectResource\Pages\KanbanBoard;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
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

    $this->taskPendiente = Task::factory()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $this->activity->id,
        'status' => TaskStatus::Pendiente,
        'priority' => TaskPriority::Media,
    ]);

    $this->taskEnProgreso = Task::factory()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $this->activity->id,
        'status' => TaskStatus::EnProgreso,
        'priority' => TaskPriority::Alta,
    ]);
});

// ─── Kanban ───────────────────────────────────────────────────────────────────

it('renders the kanban board for a project', function () {
    $this->actingAs($this->admin);

    livewire(KanbanBoard::class, ['record' => $this->project->getKey()])
        ->assertSuccessful()
        ->assertSee($this->taskPendiente->name)
        ->assertSee($this->taskEnProgreso->name);
});

it('kanban getColumns groups tasks by status', function () {
    $this->actingAs($this->admin);

    $component = livewire(KanbanBoard::class, ['record' => $this->project->getKey()])
        ->instance();

    $columns = $component->getColumns();

    $pendienteCol = collect($columns)->first(fn ($c) => $c['status'] === TaskStatus::Pendiente);
    $progresoCol = collect($columns)->first(fn ($c) => $c['status'] === TaskStatus::EnProgreso);

    expect($pendienteCol['tasks'])->toHaveCount(1)
        ->and($progresoCol['tasks'])->toHaveCount(1);
});

it('updateTaskStatus changes task status in database', function () {
    $this->actingAs($this->admin);

    livewire(KanbanBoard::class, ['record' => $this->project->getKey()])
        ->call('updateTaskStatus', $this->taskPendiente->id, TaskStatus::EnProgreso->value);

    expect($this->taskPendiente->fresh()->status)->toBe(TaskStatus::EnProgreso);
});

it('kanban filters tasks by activity', function () {
    $this->actingAs($this->admin);

    $otherActivity = Activity::factory()->create([
        'organization_id' => $this->org->id,
        'project_id' => $this->project->id,
        'name' => 'Otra actividad',
        'order' => 2,
    ]);

    $otherTask = Task::factory()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $otherActivity->id,
        'status' => TaskStatus::Pendiente,
    ]);

    $component = livewire(KanbanBoard::class, ['record' => $this->project->getKey()])
        ->set('filterActivity', $this->activity->id)
        ->instance();

    $allTasks = collect($component->getColumns())->flatMap(fn ($c) => $c['tasks']);

    expect($allTasks->pluck('id'))->not->toContain($otherTask->id)
        ->and($allTasks->pluck('id'))->toContain($this->taskPendiente->id);
});

// ─── Gantt ────────────────────────────────────────────────────────────────────

it('renders the gantt chart page for a project', function () {
    $this->actingAs($this->admin);

    livewire(GanttChart::class, ['record' => $this->project->getKey()])
        ->assertSuccessful();
});

it('gantt getGanttTasks only returns tasks with dates', function () {
    $this->actingAs($this->admin);

    Task::factory()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $this->activity->id,
        'start_date' => now()->subDays(5),
        'due_date' => now()->addDays(5),
        'status' => TaskStatus::EnProgreso,
    ]);

    $component = livewire(GanttChart::class, ['record' => $this->project->getKey()])
        ->instance();

    $tasks = $component->getGanttTasks();

    expect($tasks)->not->toBeEmpty()
        ->and(collect($tasks)->every(fn ($t) => isset($t['start'], $t['end'])))->toBeTrue();
});

// ─── Exportación ─────────────────────────────────────────────────────────────

it('export generates xlsx file with correct columns', function () {
    Excel::fake();

    $this->actingAs($this->admin);

    Excel::download(new TasksExport($this->project), 'tareas.xlsx');

    Excel::assertDownloaded('tareas.xlsx');
});

it('tasks export contains all project tasks', function () {
    $this->actingAs($this->admin);

    $export = new TasksExport($this->project);

    $rows = $export->query()->get();

    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('id'))->toContain($this->taskPendiente->id)
        ->and($rows->pluck('id'))->toContain($this->taskEnProgreso->id);
});

it('tasks export excludes tasks from other projects', function () {
    $this->actingAs($this->admin);

    $otherProject = Project::factory()->create([
        'organization_id' => $this->org->id,
        'client_id' => $this->client->id,
        'status_id' => $this->status->id,
    ]);
    $otherActivity = Activity::factory()->create([
        'organization_id' => $this->org->id,
        'project_id' => $otherProject->id,
    ]);
    $otherTask = Task::factory()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $otherActivity->id,
    ]);

    $export = new TasksExport($this->project);
    $ids = $export->query()->pluck('id');

    expect($ids)->not->toContain($otherTask->id);
});
