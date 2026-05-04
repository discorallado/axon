<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FatTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'workflow_config',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'workflow_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(FatTemplateSection::class)->orderBy('order');
    }

    public function items(): HasMany
    {
        return $this->hasManyThrough(
            FatTemplateItem::class,
            FatTemplateSection::class,
            'template_id',
            'id',
            'id',
            'section_id'
        );
    }

    public function roleSignatures(): HasMany
    {
        return $this->hasMany(TemplateRoleSignature::class)->orderBy('approval_order');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(FatExecution::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getHierarchicalItemsAttribute()
    {
        return $this->items()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();
    }

    public static function generateCode(string $category = 'FAT'): string
    {
        $prefix = strtoupper(substr($category, 0, 3));
        $lastNumber = static::where('code', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('code');
        
        if ($lastNumber) {
            $number = (int) substr($lastNumber, strrpos($lastNumber, '-') + 1) + 1;
        } else {
            $number = 1;
        }
        
        return sprintf('%s-FAT-%03d', $prefix, $number);
    }
}
