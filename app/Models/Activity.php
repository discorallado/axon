<?php

namespace App\Models;

use App\Enums\ActivityStatus;
use App\Enums\TaskStatus;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;

class Activity extends Model
{
    use HasAttachments, HasFactory, HasFilamentComments, HasOrganizationScope, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'project_id',
        'name',
        'description',
        'order',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'order' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('order')->orderBy('created_at');
    }

    /**
     * Estado calculado desde las tareas — nunca se persiste.
     *
     *   Todas completadas  → Completada
     *   Alguna activa      → EnProgreso
     *   Resto              → Pendiente
     */
    public function getStatusAttribute(): ActivityStatus
    {
        if ($this->relationLoaded('tasks')) {
            $tasks = $this->tasks;
        } else {
            // Evita cargar la colección completa: solo los conteos necesarios.
            $total = $this->tasks()->count();
            if ($total === 0) {
                return ActivityStatus::Pendiente;
            }
            $completed = $this->tasks()->where('tasks.status', TaskStatus::Completada->value)->count();
            if ($completed === $total) {
                return ActivityStatus::Completada;
            }
            $active = $this->tasks()->whereIn('tasks.status', [
                TaskStatus::EnProgreso->value,
                TaskStatus::EnRevision->value,
                TaskStatus::Bloqueada->value,
            ])->count();

            return $active > 0 ? ActivityStatus::EnProgreso : ActivityStatus::Pendiente;
        }

        if ($tasks->isEmpty()) {
            return ActivityStatus::Pendiente;
        }

        if ($tasks->every(fn ($t) => $t->status === TaskStatus::Completada)) {
            return ActivityStatus::Completada;
        }

        $activeStates = [TaskStatus::EnProgreso, TaskStatus::EnRevision, TaskStatus::Bloqueada];
        if ($tasks->contains(fn ($t) => in_array($t->status, $activeStates))) {
            return ActivityStatus::EnProgreso;
        }

        return ActivityStatus::Pendiente;
    }

    public function completionPercentage(): float
    {
        if ($this->relationLoaded('tasks')) {
            $total = $this->tasks->count();

            return $total === 0 ? 0 : round(
                $this->tasks->filter(fn ($t) => $t->status === TaskStatus::Completada)->count() / $total * 100,
                1
            );
        }

        $total = $this->tasks()->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $this->tasks()->where('tasks.status', TaskStatus::Completada->value)->count();

        return round($completed / $total * 100, 1);
    }
}
