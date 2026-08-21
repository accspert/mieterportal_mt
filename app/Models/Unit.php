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
        'current_tenant_id',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function currentTenant()
    {
        return $this->belongsTo(Tenant::class, 'current_tenant_id');
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
