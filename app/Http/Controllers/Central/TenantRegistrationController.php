<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\TenantOnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantRegistrationController extends Controller
{
    public function __construct(
        protected TenantOnboardingService $onboardingService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'subdomain' => 'required|alpha_dash|unique:domains,domain',
            'custom_domain' => 'nullable|string|unique:domains,domain',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:50',
            'plan' => 'nullable|string|in:basic,standard,enterprise',
        ]);

        $tenant = $this->onboardingService->createTenant($validated);

        return response()->json([
            'message' => 'Hausverwaltung erfolgreich registriert.',
            'tenant' => $tenant->load('domains'),
        ], 201);
    }
}
