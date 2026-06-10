<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasOrganizationScope
{
    public static function bootHasOrganizationScope(): void
    {
        static::addGlobalScope(new BelongsToOrganizationScope);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
