<?php

namespace Tests;

use App\Policies\PropertyPolicy;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base test case for Pest/PHPUnit tests.
 *
 * Declaring dynamic properties here helps static analyzers (Intelephense/Psalm)
 * recognize properties assigned in test closures (e.g., via `beforeEach`).
 *
 * @property PropertyPolicy $policy
 */
abstract class TestCase extends BaseTestCase
{
    protected ?PropertyPolicy $policy = null;
}
