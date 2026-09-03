<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // No Node build exists in CI's backend job or any bare container —
        // the 13-testing-qa §3 gate-1 contract demands tests run with ZERO
        // build artifacts. Render views with a stubbed Vite response.
        $this->withoutVite();
    }
}
