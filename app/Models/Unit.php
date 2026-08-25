<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'number',
        'floor',
        'area',
        'rooms',
        'status',
        'current_renter_id',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function currentRenter()
    {
        return $this->belongsTo(Renter::class, 'current_renter_id');
    }

    public function currentTenant()
    {
        return $this->currentRenter();
    }

    public function renter()
    {
        return $this->currentRenter();
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
