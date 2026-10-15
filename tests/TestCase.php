<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests don't need the compiled React files (npm run build).
        $this->withoutVite();

        // Make test requests look like they come from the React app on this
        // site, so the API uses the login session (Sanctum "stateful" requests).
        $this->withHeaders([
            'Accept' => 'application/json',
            'Referer' => config('app.url'),
        ]);
    }
}
