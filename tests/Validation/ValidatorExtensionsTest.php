<?php namespace Hampel\Tlds\Validation;

use Hampel\Tlds\Tlds;
use Hampel\Validate\Validator;
use PHPUnit\Framework\TestCase;
use Illuminate\Validation\Factory;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Translation\Translator;

use Mockery;

class ValidatorExtensionsTest extends TestCase
{
	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	public function testValidateDomain()
	{
		$validator = Mockery::mock(Validator::class);
		$tlds = Mockery::mock(Tlds::class);

		$extensions = new ValidatorExtensions($validator, $tlds);

		$container = Mockery::mock(Container::class);
		$translator = Mockery::mock(Translator::class);

		$container->shouldReceive('make')->once()->with(ValidatorExtensions::class)->andReturn($extensions);
		$tlds->shouldReceive('get')->once()->with()->andReturn(['com', 'net', 'co']);
		$validator->shouldReceive('isDomain')->once()->with('example.com', Mockery::on(function($data)
		{
			$this->assertTrue(is_array($data));
			$this->assertEquals(3, count($data));
			$this->assertEquals('com', $data[0]);
			$this->assertEquals('net', $data[1]);
			return true;
		}))->andReturn(true);

		$factory = new Factory($translator, $container);
		$factory->extend('domain', ValidatorExtensions::class . '@validateDomain', ':attribute must be a valid domain name');
		$validator = $factory->make(['foo' => 'example.com'], ['foo' => 'domain']);
		$this->assertTrue($validator->passes());
	}

	public function testValidateDomainFails()
	{
		$validator = Mockery::mock(Validator::class);
		$tlds = Mockery::mock(Tlds::class);

		$extensions = new ValidatorExtensions($validator, $tlds);

		$container = Mockery::mock(Container::class);
		$translator = Mockery::mock(Translator::class);

		$container->shouldReceive('make')->once()->with(ValidatorExtensions::class)->andReturn($extensions);
		$tlds->shouldReceive('get')->once()->with()->andReturn(['com', 'net', 'co']);
		$validator->shouldReceive('isDomain')->once()->with('example.invalid-tld', Mockery::on(function($data)
		{
			$this->assertTrue(is_array($data));
			$this->assertEquals(3, count($data));
			$this->assertEquals('com', $data[0]);
			$this->assertEquals('net', $data[1]);
			return true;
		}))->andReturn(false);
		$translator->shouldReceive('get')->once()->with('validation.custom')->andReturn('validation.custom');
		$translator->shouldReceive('get')->once()->with('validation.custom.foo.domain')->andReturn('validation.custom.foo.domain');
		$translator->shouldReceive('get')->once()->with('validation.domain')->andReturn('validation.domain');
		$translator->shouldReceive('get')->once()->with('validation.attributes')->andReturn('validation.attributes');
		$translator->shouldReceive('get')->once()->with('validation.values.foo.example.invalid-tld')->andReturn('validation.values.foo.example.invalid-tld');

		$factory = new Factory($translator, $container);
		$factory->extend('domain', ValidatorExtensions::class . '@validateDomain', ':attribute must be a valid domain name');
		$validator = $factory->make(['foo' => 'example.invalid-tld'], ['foo' => 'domain']);
		$this->assertTrue($validator->fails());

		$messages = $validator->messages();
		$this->assertInstanceOf(MessageBag::class, $messages);
		$this->assertEquals('foo must be a valid domain name', $messages->first('foo'));
	}

	public function testValidateDomainIn()
	{
		$validator = Mockery::mock(Validator::class);
		$tlds = Mockery::mock(Tlds::class);

		$extensions = new ValidatorExtensions($validator, $tlds);

		$container = Mockery::mock(Container::class);
		$translator = Mockery::mock(Translator::class);

		$container->shouldReceive('make')->once()->with(ValidatorExtensions::class)->andReturn($extensions);
		$validator->shouldReceive('isDomain')->once()->with('example.com', Mockery::on(function($data)
		{
			$this->assertTrue(is_array($data));
			$this->assertEquals(3, count($data));
			$this->assertEquals('com', $data[0]);
			$this->assertEquals('net', $data[1]);
			return true;
		}))->andReturn(true);

		$factory = new Factory($translator, $container);
		$factory->extend('domain_in', ValidatorExtensions::class . '@validateDomainIn', ':attribute TLD must be one of :values');
		$validator = $factory->make(['foo' => 'example.com'], ['foo' => 'domain_in:com,net,co']);
		$this->assertTrue($validator->passes());
	}

	public function testValidateDomainInFails()
	{
		$validator = Mockery::mock(Validator::class);
		$tlds = Mockery::mock(Tlds::class);

		$extensions = new ValidatorExtensions($validator, $tlds);

		$container = Mockery::mock(Container::class);
		$translator = Mockery::mock(Translator::class);

		$container->shouldReceive('make')->once()->with(ValidatorExtensions::class)->andReturn($extensions);
		$validator->shouldReceive('isDomain')->once()->with('example.invalid-tld', Mockery::on(function($data)
		{
			$this->assertTrue(is_array($data));
			$this->assertEquals(3, count($data));
			$this->assertEquals('com', $data[0]);
			$this->assertEquals('net', $data[1]);
			return true;
		}))->andReturn(false);
		$translator->shouldReceive('get')->once()->with('validation.custom')->andReturn('validation.custom');
		$translator->shouldReceive('get')->once()->with('validation.custom.foo.domain_in')->andReturn('validation.custom.foo.domain_in');
		$translator->shouldReceive('get')->once()->with('validation.domain_in')->andReturn('validation.domain_in');
		$translator->shouldReceive('get')->once()->with('validation.attributes')->andReturn('validation.attributes');
		$translator->shouldReceive('get')->once()->with('validation.values.foo.example.invalid-tld')->andReturn('validation.values.foo.example.invalid-tld');
		$container->shouldReceive('make')->once()->with(ValidatorExtensions::class)->andReturn($extensions);

		$factory = new Factory($translator, $container);
		$factory->extend('domain_in', ValidatorExtensions::class . '@validateDomainIn', ':attribute TLD must be one of :values');
		$factory->replacer('domain_in', ValidatorExtensions::class . '@replaceDomainIn');
		$validator = $factory->make(['foo' => 'example.invalid-tld'], ['foo' => 'domain_in:com,net,co']);
		$this->assertTrue($validator->fails());

		$messages = $validator->messages();
		$this->assertInstanceOf(MessageBag::class, $messages);
		$this->assertEquals('foo TLD must be one of com, net, co', $messages->first('foo'));
	}
}
