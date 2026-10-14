<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\StoreRegistrationRequest;
use App\Http\Requests\Central\StoreTenantRegistrationRequest;
use App\Models\CompanyTenant;
use App\Models\TenantRegistration;
use App\Notifications\RegistrationConfirmationNotification;
use App\Services\TenantOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Self-service sign-up: form → e-mail confirmation → property management is created → invitation mail.
 */
class RegistrationController extends Controller
{
    public function __construct(protected TenantOnboardingService $onboardingService) {}

    /**
     * Confirmation links stay valid for this many hours.
     */
    private const CONFIRMATION_TTL_HOURS = 48;

    public function create(Request $request): View
    {
        $plans = config('plans.plans');
        $requestedPlan = (string) $request->query('paket');

        return view('central.register', [
            'plans' => $plans,
            'selectedPlan' => old('plan', array_key_exists($requestedPlan, $plans) ? $requestedPlan : config('plans.default')),
        ]);
    }

    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        // Bots fill in the hidden honeypot field: pretend success, store nothing.
        if ($request->filled('website')) {
            return redirect()->route('central.register.sent');
        }

        $registration = TenantRegistration::query()->create([
            ...$request->safe()->only(['plan', 'company_name', 'admin_name', 'email', 'phone']),
            'email' => mb_strtolower($request->validated('email')),
            'terms_accepted_at' => now(),
        ]);

        $confirmationUrl = URL::temporarySignedRoute(
            'central.register.confirm',
            now()->addHours(self::CONFIRMATION_TTL_HOURS),
            ['registration' => $registration->getKey()],
        );

        Notification::route('mail', $registration->email)
            ->notify(new RegistrationConfirmationNotification($confirmationUrl, $registration->admin_name, $registration->company_name));

        return redirect()->route('central.register.sent')->with('registration_email', $registration->email);
    }

    public function sent(): View
    {
        return view('central.register-sent');
    }

    public function confirm(TenantRegistration $registration): View
    {
        if (! $registration->isConfirmed()) {
            $slug = $this->uniqueSlug($registration->company_name);

            // Atomic: a double click on the link must not create two property managements.
            $isFirstConfirmation = TenantRegistration::query()
                ->whereKey($registration->getKey())
                ->whereNull('confirmed_at')
                ->update(['confirmed_at' => now(), 'slug' => $slug]) === 1;

            if ($isFirstConfirmation) {
                $tenant = $this->onboardingService->createTenant([
                    'company_name' => $registration->company_name,
                    'slug' => $slug,
                    'admin_name' => $registration->admin_name,
                    'admin_email' => $registration->email,
                    'phone' => $registration->phone,
                    'plan' => $registration->plan,
                ]);

                $registration->update(['tenant_id' => $tenant->getTenantKey()]);
            }
        }

        return view('central.register-confirmed', ['registration' => $registration->fresh()]);
    }

    /**
     * Derives the internal identifier of the property management from the company name.
     */
    private function uniqueSlug(string $companyName): string
    {
        $base = trim(Str::limit(Str::slug($companyName), 40, ''), '-') ?: 'verwaltung';

        if (in_array($base, StoreTenantRegistrationRequest::RESERVED_SLUGS, true)) {
            $base .= '-verwaltung';
        }

        $candidate = $base;

        for ($suffix = 2; $this->isSlugTaken($candidate); $suffix++) {
            $candidate = "{$base}-{$suffix}";
        }

        return $candidate;
    }

    private function isSlugTaken(string $slug): bool
    {
        return CompanyTenant::query()->whereKey($slug)->exists()
            || TenantRegistration::query()->where('slug', $slug)->exists();
    }
}
