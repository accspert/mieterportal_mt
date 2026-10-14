<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompanyTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use BelongsToCompanyTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'manager_id',
        'name',
        'street',
        'postal_code',
        'city',
        'country',
        'description',
        'contact_person',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
