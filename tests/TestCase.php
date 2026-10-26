<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ordering tests exercise pricing and delivery rules as guests; AccountsAndRolesTest covers the sign-in requirement.
        config(['restaurant.order_requires_login' => false]);
    }
}
