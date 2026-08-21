<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();

        if (! empty($data['path']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($data['path'])) {
            $data['size'] = \Illuminate\Support\Facades\Storage::disk('public')->size($data['path']);
            $data['mime_type'] = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($data['path']) ?? 'application/pdf';
        } else {
            $data['mime_type'] = $data['mime_type'] ?? 'application/pdf';
        }

        return $data;
    }
}
