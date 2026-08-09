<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Parallax\FilamentComments\Models\Traits\HasFilamentComments;

class Supplier extends Model
{
    use HasAttachments, HasFactory, HasFilamentComments, HasOrganizationScope, HasUlids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'rut',
        'email',
        'phone',
        'address',
        'contact_name',
        'bank_name',
        'bank_account',
        'notes',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
