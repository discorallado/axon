<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FatExecution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'project_id',
        'template_id',
        'status',
        'execution_date',
        'comments',
        'metadata',
        'created_by',
        'updated_by',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'execution_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FatTemplate::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ExecutionRevision::class)->orderByDesc('version');
    }

    public function activeRevision(): BelongsTo
    {
        return $this->belongsTo(ExecutionRevision::class, 'active_revision_id')
            ->where('is_active', true);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function latestRevision()
    {
        return $this->revisions()->latest('version')->first();
    }

    public function getCompletionPercentageAttribute(): float
    {
        $revision = $this->latestRevision();
        if (!$revision) {
            return 0.0;
        }

        $totalItems = $this->template->items()->count();
        if ($totalItems === 0) {
            return 0.0;
        }

        $completedItems = $revision->results()
            ->whereNotNull('result')
            ->count();

        return round(($completedItems / $totalItems) * 100, 2);
    }

    public function getResultDistributionAttribute(): array
    {
        $revision = $this->latestRevision();
        if (!$revision) {
            return ['C' => 0, 'NC' => 0, 'NA' => 0];
        }

        return [
            'C' => $revision->results()->where('result', 'C')->count(),
            'NC' => $revision->results()->where('result', 'NC')->count(),
            'NA' => $revision->results()->where('result', 'NA')->count(),
        ];
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'pending_review']);
    }

    public static function generateCode(string $projectCode): string
    {
        $year = date('Y');
        $prefix = substr($projectCode, 0, strpos($projectCode, '-') ?: 3);
        $lastNumber = static::where('code', 'like', "{$prefix}%-{$year}%")
            ->orderByDesc('id')
            ->value('code');
        
        if ($lastNumber) {
            $number = (int) substr($lastNumber, strrpos($lastNumber, '-') + 1) + 1;
        } else {
            $number = 1;
        }
        
        return sprintf('%s-FAT-%d-%03d', $prefix, $year, $number);
    }
}
