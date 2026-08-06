<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // The Herd/CLI runner injects a phantom HTTP_ACCEPT_LANGUAGE (e.g. "en-us,en;q=0.5")
        // into every request, which would make locale resolution pick up a client
        // Accept-Language that no test actually sent. Strip it so tests represent the real
        // default (no client Accept-Language); tests that need one set it explicitly.
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE'], $_ENV['HTTP_ACCEPT_LANGUAGE']);
        putenv('HTTP_ACCEPT_LANGUAGE');

        parent::setUp();

        // Symfony's Request::create() (used by the HTTP test client) hard-codes a default
        // Accept-Language of "en-us,en;q=0.5". Blank it so tests behave as a client that
        // sent no Accept-Language; tests exercising it set it explicitly per request.
        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);
    }
}
