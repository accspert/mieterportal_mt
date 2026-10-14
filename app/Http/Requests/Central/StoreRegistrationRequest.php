<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * E-mail addresses are compared case-insensitively, so they are stored in lower case.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'plan' => ['required', 'string', Rule::in(static::availablePlans())],
            'company_name' => ['required', 'string', 'min:2', 'max:120'],
            'admin_name' => ['required', 'string', 'max:120'],
            // One account per e-mail address across the platform: the login finds the property management by it.
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'terms' => ['accepted'],
            // Honeypot: invisible for people, filled in by bots (handled in the controller).
            'website' => ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Mit dieser E-Mail-Adresse besteht bereits ein Konto. Bitte melden Sie sich an oder verwenden Sie eine andere Adresse.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'plan' => 'Paket',
            'company_name' => 'Name der Verwaltung',
            'admin_name' => 'Ihr Name',
            'email' => 'E-Mail-Adresse',
            'phone' => 'Telefon',
            'terms' => 'AGB und Datenschutzerklärung',
        ];
    }

    /**
     * @return list<string>
     */
    public static function availablePlans(): array
    {
        return array_keys(array_filter(config('plans.plans'), fn (array $plan): bool => $plan['available']));
    }
}
