<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompanyTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use BelongsToCompanyTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'number',
        'floor',
        'area',
        'rooms',
        'status',
        'current_renter_id',
        'current_tenant_id',
    ];

    public function setCurrentTenantIdAttribute($value): void
    {
        $this->attributes['current_renter_id'] = $value;
    }

    public function getCurrentTenantIdAttribute(): ?int
    {
        return $this->attributes['current_renter_id'] ?? null;
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function currentRenter(): BelongsTo
    {
        return $this->belongsTo(Renter::class, 'current_renter_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
