<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompanyTenant;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Audit log entry, limited to the active property management like every other record.
 */
class Activity extends SpatieActivity
{
    use BelongsToCompanyTenant;
}
