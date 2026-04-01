<?php

namespace Tests;

use App\Support\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set default tenant context for all tests
        TenantContext::set(1);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }
}
