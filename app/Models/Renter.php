<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompanyTenant;
use Database\Factories\RenterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Renter extends Model
{
    /** @use HasFactory<RenterFactory> */
    use BelongsToCompanyTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'manager_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'move_in',
        'move_out',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'move_in' => 'date',
            'move_out' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'current_renter_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'renter_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'renter_id');
    }
}
