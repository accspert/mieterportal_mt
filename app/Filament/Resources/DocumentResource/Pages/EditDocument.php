<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['path']) && Storage::disk('public')->exists($data['path'])) {
            $data['size'] = Storage::disk('public')->size($data['path']);
            $data['mime_type'] = Storage::disk('public')->mimeType($data['path']) ?: 'application/octet-stream';
        }

        return $data;
    }
}
