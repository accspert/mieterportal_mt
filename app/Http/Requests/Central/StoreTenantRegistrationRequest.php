<?php

declare(strict_types=1);

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRegistrationRequest extends FormRequest
{
    /**
     * Identifiers (slugs) that must never be assigned to a property management.
     *
     * @var list<string>
     */
    public const RESERVED_SLUGS = [
        'admin', 'api', 'app', 'assets', 'cdn', 'dev', 'ftp', 'mail', 'portal',
        'smtp', 'staging', 'static', 'status', 'support', 'test', 'www',
    ];

    /**
     * Onboarding creates databases, so it is only allowed with the configured onboarding token.
     */
    public function authorize(): bool
    {
        $onboardingToken = config('tenancy.onboarding_token');

        return filled($onboardingToken)
            && is_string($this->bearerToken())
            && hash_equals($onboardingToken, $this->bearerToken());
    }

    /**
     * E-mail addresses are compared case-insensitively, so they are stored in lower case.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('admin_email'))) {
            $this->merge(['admin_email' => mb_strtolower(trim($this->input('admin_email')))]);
        }
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:63',
                'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/',
                Rule::notIn(self::RESERVED_SLUGS),
                Rule::unique('company_tenants', 'id'),
            ],
            'admin_name' => ['required', 'string', 'max:255'],
            // One account per e-mail address across the platform: the login finds the property management by it.
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
            'plan' => ['nullable', 'string', Rule::in(array_keys(config('plans.plans')))],
        ];
    }
}
