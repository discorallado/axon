<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasOrganizationScope
{
    public static function bootHasOrganizationScope(): void
    {
        static::addGlobalScope(new BelongsToOrganizationScope);

        static::creating(function ($model) {
            if (empty($model->organization_id) && Auth::check() && Auth::user()->organization_id) {
                $model->organization_id = Auth::user()->organization_id;
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
