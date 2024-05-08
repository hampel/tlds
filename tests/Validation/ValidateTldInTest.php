<?php namespace Hampel\Tlds\Tests\Validation;

use Hampel\Tlds\Validation\TldIn;
use Illuminate\Support\Facades\Validator;

class ValidateTldInTest extends ValidateUnitTestCase
{
    public function testTldInFailsValidation()
    {
        $validator = Validator::make(
            ['tld' => 'foo'],
            ['tld' => [new TldIn(['com', 'net', 'au'])]]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals('tld must end in one of com, net, au', $validator->errors()->first('tld'));
    }

    public function testTldPassesValidation()
    {
        $validator = Validator::make(
            ['tld' => 'com'],
            ['tld' => [new TldIn(['com', 'net', 'au'])]]
        );

        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->first('tld'));
    }

    public function test2ldPassesValidation()
    {
        $validator = Validator::make(
            ['tld' => 'foo.au'],
            ['tld' => [new TldIn(['com', 'net', 'au'])]]
        );

        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->first('tld'));
    }
}
