<?php

use App\Filament\Pages\SettingsPage;
use App\Filament\Resources\ProjectResource\Pages\GanttChart;
use App\Filament\Resources\ProjectResource\Pages\KanbanBoard;
use App\Filament\Widgets\MonthlyTaskCreationWidget;
use App\Filament\Widgets\ProjectProgressWidget;
use App\Filament\Widgets\ProjectStatsWidget;
use App\Filament\Widgets\TasksByStatusWidget;
use App\Filament\Widgets\TeamContributionWidget;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Task;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
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
        'start_date' => now()->subDays(5),
        'end_date' => now()->addDays(10),
    ]);

    $this->task = Task::factory()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $this->activity->id,
        'start_date' => now()->subDays(3),
        'due_date' => now()->addDays(7),
    ]);
});

// ── UserSetting ────────────────────────────────────────────────────────────────

it('guarda y recupera una UserSetting', function () {
    UserSetting::set($this->admin->id, 'theme_color', 'green');

    expect(UserSetting::get($this->admin->id, 'theme_color'))->toBe('green');
});

it('retorna el default cuando no existe la setting', function () {
    expect(UserSetting::get($this->admin->id, 'theme_color', 'blue'))->toBe('blue');
});

it('actualiza una UserSetting existente sin duplicar filas', function () {
    UserSetting::set($this->admin->id, 'theme_color', 'red');
    UserSetting::set($this->admin->id, 'theme_color', 'violet');

    expect(UserSetting::get($this->admin->id, 'theme_color'))->toBe('violet');
    expect(UserSetting::where('user_id', $this->admin->id)->where('key', 'theme_color')->count())->toBe(1);
});

it('no mezcla configuraciones entre usuarios', function () {
    $other = User::factory()->create(['organization_id' => $this->org->id]);

    UserSetting::set($this->admin->id, 'theme_color', 'red');
    UserSetting::set($other->id, 'theme_color', 'green');

    expect(UserSetting::get($this->admin->id, 'theme_color'))->toBe('red');
    expect(UserSetting::get($other->id, 'theme_color'))->toBe('green');
});

// ── SettingsPage ───────────────────────────────────────────────────────────────

it('renderiza la SettingsPage', function () {
    actingAs($this->admin);

    livewire(SettingsPage::class)->assertSuccessful();
});

it('guarda configuración desde SettingsPage', function () {
    actingAs($this->admin);

    livewire(SettingsPage::class)
        ->set('theme_color', 'teal')
        ->set('notify_email', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(UserSetting::get($this->admin->id, 'theme_color'))->toBe('teal');
    expect(UserSetting::get($this->admin->id, 'notify_email'))->toBe('0');
});

// ── Gantt frappe ───────────────────────────────────────────────────────────────

it('updateTaskDates persiste las fechas', function () {
    actingAs($this->admin);

    $newStart = now()->addDay()->format('Y-m-d');
    $newEnd = now()->addDays(10)->format('Y-m-d');

    livewire(GanttChart::class, ['record' => $this->project->getRouteKey()])
        ->call('updateTaskDates', $this->task->id, $newStart, $newEnd);

    $this->task->refresh();
    expect($this->task->start_date->format('Y-m-d'))->toBe($newStart);
    expect($this->task->due_date->format('Y-m-d'))->toBe($newEnd);
});

// ── Kanban ─────────────────────────────────────────────────────────────────────

it('renderiza el KanbanBoard mostrando la tarea', function () {
    actingAs($this->admin);

    livewire(KanbanBoard::class, ['record' => $this->project->getRouteKey()])
        ->assertSuccessful()
        ->assertSee($this->task->name);
});

it('muestra múltiples avatares de asignados en el kanban', function () {
    actingAs($this->admin);

    $user2 = User::factory()->create(['organization_id' => $this->org->id, 'name' => 'Ana González']);
    $this->task->assignees()->attach([$this->admin->id, $user2->id]);

    livewire(KanbanBoard::class, ['record' => $this->project->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('AG');
});

// ── Widgets ────────────────────────────────────────────────────────────────────

it('TasksByStatusWidget renderiza sin errores', function () {
    actingAs($this->admin);
    livewire(TasksByStatusWidget::class)->assertSuccessful();
});

it('MonthlyTaskCreationWidget renderiza sin errores', function () {
    actingAs($this->admin);
    livewire(MonthlyTaskCreationWidget::class)->assertSuccessful();
});

it('ProjectProgressWidget renderiza sin errores', function () {
    actingAs($this->admin);
    livewire(ProjectProgressWidget::class)->assertSuccessful();
});

it('TeamContributionWidget renderiza sin errores', function () {
    actingAs($this->admin);
    livewire(TeamContributionWidget::class)->assertSuccessful();
});

it('ProjectStatsWidget muestra stat de tareas vencidas', function () {
    actingAs($this->admin);

    Task::factory()->create([
        'organization_id' => $this->org->id,
        'activity_id' => $this->activity->id,
        'due_date' => now()->subDays(3),
        'status' => 'pendiente',
    ]);

    livewire(ProjectStatsWidget::class)->assertSuccessful();
});
