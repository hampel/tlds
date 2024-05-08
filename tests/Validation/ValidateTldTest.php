<?php namespace Hampel\Tlds\Tests\Validation;

use Hampel\Tlds\Validation\Tld;
use Illuminate\Support\Facades\Validator;

class ValidateTldTest extends ValidateUnitTestCase
{
    public function testTldFailsValidation()
    {
        $validator = Validator::make(
            ['tld' => 'foo'],
            ['tld' => [new Tld]]
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals('tld must have a valid top-level-domain (TLD)', $validator->errors()->first('tld'));
    }

    public function testTldPassesValidation()
    {
        $validator = Validator::make(
            ['tld' => 'com'],
            ['tld' => [new Tld]]
        );

        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->first('tld'));
    }

    public function test2ldPassesValidation()
    {
        $validator = Validator::make(
            ['tld' => 'foo.au'],
            ['tld' => [new Tld]]
        );

        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->first('tld'));
    }
}
