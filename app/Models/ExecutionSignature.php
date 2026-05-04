<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecutionSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'revision_id',
        'role_signature_id',
        'signed_by',
        'signer_name',
        'signer_title',
        'signature_file_path',
        'signed_from_ip',
        'signed_at',
        'comments',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function revision(): BelongsTo
    {
        return $this->belongsTo(ExecutionRevision::class);
    }

    public function roleSignature(): BelongsTo
    {
        return $this->belongsTo(TemplateRoleSignature::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function getSignerDisplayNameAttribute(): string
    {
        if ($this->signer_name) {
            return $this->signer_name;
        }

        return $this->signer?->name ?? 'Desconocido';
    }

    public function getFormattedSignedAtAttribute(): string
    {
        return $this->signed_at?->format('d/m/Y H:i') ?? '';
    }

    public function hasSignatureFile(): bool
    {
        return !empty($this->signature_file_path);
    }

    public function getSignatureUrlAttribute(): ?string
    {
        if (!$this->signature_file_path) {
            return null;
        }

        return asset('storage/' . $this->signature_file_path);
    }

    public function isInternal(): bool
    {
        return $this->roleSignature?->isInternalSigner() ?? false;
    }

    public function isExternal(): bool
    {
        return $this->roleSignature?->isExternalSigner() ?? false;
    }
}
