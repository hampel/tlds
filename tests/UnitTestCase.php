<?php namespace Hampel\Tlds\Tests;

use Hampel\Tlds\TldServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class UnitTestCase extends TestCase
{
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
