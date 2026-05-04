<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateRoleSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'role_name',
        'role_display_name',
        'approval_order',
        'is_required',
        'signer_type',
        'config',
    ];

    protected $casts = [
        'approval_order' => 'integer',
        'is_required' => 'boolean',
        'config' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(FatTemplate::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ExecutionSignature::class);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeInternal($query)
    {
        return $query->where('signer_type', 'internal');
    }

    public function scopeExternal($query)
    {
        return $query->where('signer_type', 'external');
    }

    public function isInternalSigner(): bool
    {
        return $this->signer_type === 'internal';
    }

    public function isExternalSigner(): bool
    {
        return $this->signer_type === 'external';
    }
}
