<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Document extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'filename',
        'path',
        'mime_type',
        'size',
        'category',
        'version',
        'amount',
        'due_date',
        'uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['filename', 'category', 'version'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Dokument wurde {$eventName}");
    }

    public function documentable()
    {
        return $this->morphTo();
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the lowercase file extension from path, filename or MIME type.
     */
    public function getFileExtensionAttribute(): string
    {
        if ($this->path) {
            $ext = pathinfo($this->path, PATHINFO_EXTENSION);
            if ($ext) {
                return strtolower($ext);
            }
        }

        if ($this->filename) {
            $ext = pathinfo($this->filename, PATHINFO_EXTENSION);
            if ($ext) {
                return strtolower($ext);
            }
        }

        if ($this->mime_type) {
            return match ($this->mime_type) {
                'image/jpeg', 'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                'image/svg+xml' => 'svg',
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                default => 'pdf',
            };
        }

        return 'pdf';
    }

    /**
     * Determine whether the document is an image (JPEG, PNG, WEBP, GIF, etc.).
     */
    public function getIsImageAttribute(): bool
    {
        if ($this->mime_type && str_starts_with($this->mime_type, 'image/')) {
            return true;
        }

        return in_array($this->file_extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp']);
    }

    /**
     * Determine whether the document is a PDF.
     */
    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf' || $this->file_extension === 'pdf';
    }

    /**
     * Human-readable file type label (e.g. JPEG-Bild, PDF-Dokument).
     */
    public function getTypeLabelAttribute(): string
    {
        if ($this->is_image) {
            return 'Bilddatei ('.strtoupper($this->file_extension).')';
        }

        return match ($this->file_extension) {
            'pdf' => 'PDF-Dokument',
            'doc', 'docx' => 'Word-Dokument',
            'xls', 'xlsx' => 'Excel-Tabelle',
            'csv' => 'CSV-Datei',
            'zip', 'rar', '7z' => 'ZIP-Archiv',
            default => 'Dokument ('.strtoupper($this->file_extension).')',
        };
    }

    /**
     * Badge configuration for UI display (label, background color, text color).
     */
    public function getFileTypeBadgeAttribute(): array
    {
        $ext = strtoupper($this->file_extension);

        if ($this->is_image) {
            return [
                'label' => in_array($ext, ['JPG', 'JPEG']) ? 'JPG' : $ext,
                'bg' => 'rgba(0, 86, 179, 0.12)',
                'color' => '#0056B3',
                'type' => 'image',
            ];
        }

        return match ($this->file_extension) {
            'pdf' => [
                'label' => 'PDF',
                'bg' => 'rgba(220, 38, 38, 0.12)',
                'color' => '#dc2626',
                'type' => 'pdf',
            ],
            'doc', 'docx' => [
                'label' => 'DOC',
                'bg' => 'rgba(37, 99, 235, 0.12)',
                'color' => '#2563eb',
                'type' => 'word',
            ],
            'xls', 'xlsx', 'csv' => [
                'label' => 'XLS',
                'bg' => 'rgba(22, 163, 74, 0.12)',
                'color' => '#16a34a',
                'type' => 'excel',
            ],
            'zip', 'rar', '7z' => [
                'label' => 'ZIP',
                'bg' => 'rgba(217, 119, 6, 0.12)',
                'color' => '#d97706',
                'type' => 'archive',
            ],
            default => [
                'label' => $ext ?: 'FILE',
                'bg' => 'rgba(107, 114, 128, 0.12)',
                'color' => '#4b5563',
                'type' => 'file',
            ],
        };
    }

    /**
     * Ensure the downloaded filename always has the correct extension matching the actual file.
     */
    public function getDownloadFilenameAttribute(): string
    {
        $name = $this->filename ?: 'Dokument';
        $ext = $this->file_extension;

        if ($ext && ! str_ends_with(strtolower($name), '.'.strtolower($ext))) {
            $name .= '.'.$ext;
        }

        return $name;
    }
}
