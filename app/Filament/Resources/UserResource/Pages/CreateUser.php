<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/**
 * Staff accounts are created by invitation: the person sets the password personally.
 */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['email'] = mb_strtolower(trim($data['email']));
        // Never used: the invitation link sets the real password.
        $data['password'] = Str::password(40);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->record;

        $user->sendInvitation((string) tenant()?->company_name);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Einladung gesendet – die Person legt ihr Passwort selbst fest.';
    }
}
