<?php

namespace App\Filament\Superadmin\Resources\PlatformUserResource\Pages;

use App\Filament\Superadmin\Resources\PlatformUserResource;
use App\Services\PortalAccessService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use RuntimeException;

class ListPlatformUsers extends ListRecords
{
    protected static string $resource = PlatformUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('inviteSuperAdmin')
                ->label('Superadmin einladen')
                ->icon('heroicon-o-shield-check')
                ->modalDescription('Die Person erhält vollen Zugriff auf das Backoffice. Sie bekommt eine Einladung per E-Mail und legt ihr Passwort selbst fest.')
                ->modalSubmitActionLabel('Einladung senden')
                ->schema([
                    Forms\Components\TextInput::make('name')->label('Name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('email')->label('E-Mail-Adresse')->email()->required()->maxLength(255),
                ])
                ->action(function (array $data, PortalAccessService $portalAccess): void {
                    try {
                        $portalAccess->inviteSuperAdmin($data['name'], $data['email']);
                    } catch (RuntimeException $exception) {
                        Notification::make()->title('Kein Konto angelegt')->body($exception->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()->title('Einladung gesendet')->body("E-Mail an {$data['email']} wurde versendet.")->success()->send();
                }),
        ];
    }
}
