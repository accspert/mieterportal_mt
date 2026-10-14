<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompanyTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Owner extends Model
{
    use BelongsToCompanyTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'street',
        'postal_code',
        'city',
        'country',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
