<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompanyTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Ticket extends Model
{
    use BelongsToCompanyTenant, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'category',
        'priority',
        'status',
        'property_id',
        'unit_id',
        'renter_id',
        'tenant_id',
        'created_by',
        'assigned_to',
        'closed_at',
    ];

    public function setTenantIdAttribute($value): void
    {
        $this->attributes['renter_id'] = $value;
    }

    public function getTenantIdAttribute(): ?int
    {
        return $this->attributes['renter_id'] ?? null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'priority', 'category', 'assigned_to'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Ticket wurde {$eventName}");
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(Renter::class, 'renter_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
