<?php

namespace Hampel\Tlds\Tests;

use Hampel\Tlds\TldServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class UnitTestCase extends TestCase
{
    /**
     * Laravel's HandleExceptions bootstrapper replaces PHPUnit's error handler when
     * Testbench boots the application, and then discards deprecations outright while
     * running unit tests - so phpunit.xml's failOnDeprecation has nothing to fire on
     * until the strict handler is put back. Verified by probing, not by reading:
     * trigger_error(..., E_USER_DEPRECATED) on a path these tests execute left the
     * suite green at exit 0 without this line.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutDeprecationHandling();
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            TldServiceProvider::class,
        ];
    }
}
