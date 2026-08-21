<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

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

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
