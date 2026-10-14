<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Self-service sign-up of a property management, waiting for (or past) e-mail confirmation.
 */
class TenantRegistration extends Model
{
    use CentralConnection, Prunable;

    /**
     * Unconfirmed sign-ups are deleted after this many days.
     */
    public const UNCONFIRMED_RETENTION_DAYS = 7;

    protected $fillable = [
        'company_name',
        'admin_name',
        'email',
        'phone',
        'plan',
        'slug',
        'tenant_id',
        'terms_accepted_at',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'terms_accepted_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function prunable(): Builder
    {
        return static::query()
            ->whereNull('confirmed_at')
            ->where('created_at', '<', now()->subDays(self::UNCONFIRMED_RETENTION_DAYS));
    }
}
