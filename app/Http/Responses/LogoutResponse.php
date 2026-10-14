<?php

namespace App\Http\Responses;

use App\Services\CentralLoginService;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect()->away(CentralLoginService::loginUrl(['abgemeldet' => 1]));
    }
}
