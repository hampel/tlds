<?php namespace Hampel\Tlds\Tests\Validation;

use Hampel\Tlds\Facades\Tlds;
use Hampel\Tlds\Tests\UnitTestCase;

abstract class ValidateUnitTestCase extends UnitTestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $domains = [
            'au',
            'com',
            'net',
            'io',
            'org'
        ];
        Tlds::shouldReceive('get')->andReturn($domains);
    }
}
