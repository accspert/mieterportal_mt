<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\Central\StoreTenantRegistrationRequest;
use App\Services\TenantOnboardingService;
use Illuminate\Http\JsonResponse;

class TenantRegistrationController extends Controller
{
    public function __construct(protected TenantOnboardingService $onboardingService) {}

    /**
     * Creates the property management; the administrator receives an invitation mail.
     */
    public function store(StoreTenantRegistrationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $tenant = $this->onboardingService->createTenant($data);

        return response()->json([
            'message' => 'Die Hausverwaltung wurde eingerichtet. Der Administrator erhält eine Einladung per E-Mail.',
            'tenant_id' => $tenant->getTenantKey(),
        ], 201);
    }
}
