<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Store an uploaded file as an Attachment linked to a model (Ticket, Document, Message, Invoice).
     */
    public function storeAttachment(UploadedFile $file, Model $attachable, ?User $uploader = null, string $folder = 'attachments'): Attachment
    {
        $safeName = Str::random(20).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $safeName, 'public');

        return Attachment::create([
            'attachable_type' => $attachable::class,
            'attachable_id' => $attachable->getKey(),
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $uploader?->id ?? auth()->id(),
        ]);
    }

    /**
     * Store an uploaded file as a Document linked to a model (Property, Unit, Tenant, Owner).
     */
    public function storeDocument(
        UploadedFile $file,
        Model $documentable,
        ?User $uploader = null,
        string $category = 'general',
        int $version = 1,
        string $folder = 'documents'
    ): Document {
        $safeName = Str::random(20).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $safeName, 'public');

        return Document::create([
            'documentable_type' => $documentable::class,
            'documentable_id' => $documentable->getKey(),
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'size' => $file->getSize(),
            'category' => $category,
            'version' => $version,
            'uploaded_by' => $uploader?->id ?? auth()->id(),
        ]);
    }
}
