<?php

namespace App\Filament\Superadmin\Resources\CompanyTenantResource\Pages;

use App\Filament\Superadmin\Resources\CompanyTenantResource;
use App\Http\Requests\Central\StoreTenantRegistrationRequest;
use App\Services\TenantOnboardingService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Validation\Rule;

class ListCompanyTenants extends ListRecords
{
    protected static string $resource = CompanyTenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createCompanyTenant')
                ->label('Verwaltung anlegen')
                ->icon('heroicon-o-plus')
                ->modalDescription('Die Verwaltung wird sofort angelegt. Der Administrator erhält eine Einladung per E-Mail und legt sein Passwort selbst fest.')
                ->modalSubmitActionLabel('Anlegen und einladen')
                ->schema([
                    Forms\Components\TextInput::make('company_name')->label('Name der Verwaltung')->required()->maxLength(255),
                    Forms\Components\TextInput::make('slug')
                        ->label('Kennung')
                        ->helperText('Interne, eindeutige Kennung, z. B. muster-immobilien (Kleinbuchstaben, Zahlen, Bindestriche).')
                        ->required()
                        ->maxLength(63)
                        ->regex('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/')
                        ->notIn(StoreTenantRegistrationRequest::RESERVED_SLUGS)
                        ->unique('company_tenants', 'id'),
                    Forms\Components\TextInput::make('admin_name')->label('Name des Administrators')->required()->maxLength(255),
                    Forms\Components\TextInput::make('admin_email')
                        ->label('E-Mail des Administrators')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->rules([Rule::unique('users', 'email')])
                        ->dehydrateStateUsing(fn (string $state): string => mb_strtolower(trim($state))),
                    Forms\Components\Select::make('plan')
                        ->label('Paket')
                        ->options(collect(config('plans.plans'))->map(fn (array $plan): string => "{$plan['name']} – {$plan['price']}"))
                        ->default(config('plans.default'))
                        ->required(),
                ])
                ->action(function (array $data, TenantOnboardingService $onboarding): void {
                    $tenant = $onboarding->createTenant($data);

                    Notification::make()
                        ->title("{$tenant->company_name} angelegt")
                        ->body("Die Einladung ging an {$data['admin_email']}.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
