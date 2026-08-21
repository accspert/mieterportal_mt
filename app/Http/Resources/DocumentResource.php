<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'documentable_type' => $this->documentable_type,
            'documentable_id' => $this->documentable_id,
            'filename' => $this->filename,
            'path' => $this->path,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'version' => $this->version,
            'category' => $this->category,
            'uploaded_by' => $this->uploaded_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
