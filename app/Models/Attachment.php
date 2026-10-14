<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompanyTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use BelongsToCompanyTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'filename',
        'path',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Ensure the downloaded filename always has the correct extension.
     * Mirrors Document::getDownloadFilenameAttribute().
     */
    public function getDownloadFilenameAttribute(): string
    {
        $name = $this->filename ?: 'Anhang';
        $ext = pathinfo($this->path ?? '', PATHINFO_EXTENSION) ?: 'bin';

        if ($ext && ! str_ends_with(strtolower($name), '.'.strtolower($ext))) {
            $name .= '.'.$ext;
        }

        return $name;
    }
}
