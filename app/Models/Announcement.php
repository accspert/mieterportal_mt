<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompanyTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Announcement extends Model
{
    use BelongsToCompanyTenant, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'title',
        'body',
        'type',
        'property_id',
        'starts_at',
        'ends_at',
        'is_active',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'type', 'is_active', 'property_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Aushang/Mitteilung wurde {$eventName}");
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeForProperties(Builder $query, array $propertyIds): Builder
    {
        return $query->where(function ($q) use ($propertyIds) {
            $q->whereNull('property_id')->orWhereIn('property_id', $propertyIds);
        });
    }
}
