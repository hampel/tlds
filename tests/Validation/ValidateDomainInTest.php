<?php namespace Hampel\Tlds\Tests\Validation;

use Hampel\Tlds\Validation\DomainIn;
use Illuminate\Support\Facades\Validator;

class ValidateDomainInTest extends ValidateUnitTestCase
{
    public function testDomainInFailsValidation()
    {
        $validator = Validator::make(
            ['domain' => 'example.foo'],
            ['domain' => [new DomainIn(['com', 'net'])]]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals('domain TLD must be one of com, net', $validator->errors()->first('domain'));
    }

    public function testDomainInPassesValidation()
    {
        $validator = Validator::make(
            ['domain' => 'example.com'],
            ['domain' => [new DomainIn(['com', 'net'])]]
        );

        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->first('domain'));
    }
}
