<?php

namespace Hampel\Tlds\Tests\Validation;

use Hampel\Tlds\Validation\Domain;
use Illuminate\Support\Facades\Validator;

class ValidateDomainTest extends ValidateUnitTestCase
{
    public function testDomainFailsValidation(): void
    {
        $validator = Validator::make(
            ['domain' => 'example.foo'],
            ['domain' => [new Domain()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame('domain must be a valid domain name', $validator->errors()->first('domain'));
    }

    public function testDomainPassesValidation(): void
    {
        $validator = Validator::make(
            ['domain' => 'example.com'],
            ['domain' => [new Domain()]]
        );

        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->first('domain'));
    }
}
