<?php

namespace App\Models;

/**
 * @deprecated Use App\Models\Renter instead to prevent naming collisions with SaaS multi-tenancy.
 */
class Tenant extends Renter
{
    protected $table = 'renters';
}
