<?php namespace Hampel\Tlds\Validation;

use Illuminate\Validation\Factory;

use Mockery;

class ValidatorExtensionsTest extends \PHPUnit_Framework_TestCase
{
	public function testValidateDomain()
	{
		$validator = Mockery::mock('Hampel\Validate\Validator');
		$tlds = Mockery::mock('Hampel\Tlds\Tlds');

		$extensions = new ValidatorExtensions($validator, $tlds);

		$container = Mockery::mock('Illuminate\Container\Container');
		$translator = Mockery::mock('Symfony\Component\Translation\TranslatorInterface');

		$container->shouldReceive('make')->once()->with('Hampel\Tlds\Validation\ValidatorExtensions')->andReturn($extensions);
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
		$factory->extend('domain', 'Hampel\Tlds\Validation\ValidatorExtensions@validateDomain', ':attribute must be a valid domain name');
		$validator = $factory->make(['foo' => 'example.com'], ['foo' => 'domain']);
		$this->assertTrue($validator->passes());
	}

	public function testValidateDomainFails()
	{
		$validator = Mockery::mock('Hampel\Validate\Validator');
		$tlds = Mockery::mock('Hampel\Tlds\Tlds');

		$extensions = new ValidatorExtensions($validator, $tlds);

		$container = Mockery::mock('Illuminate\Container\Container');
		$translator = Mockery::mock('Symfony\Component\Translation\TranslatorInterface');

		$container->shouldReceive('make')->once()->with('Hampel\Tlds\Validation\ValidatorExtensions')->andReturn($extensions);
		$tlds->shouldReceive('get')->once()->with()->andReturn(['com', 'net', 'co']);
		$validator->shouldReceive('isDomain')->once()->with('example.invalid-tld', Mockery::on(function($data)
		{
			$this->assertTrue(is_array($data));
			$this->assertEquals(3, count($data));
			$this->assertEquals('com', $data[0]);
			$this->assertEquals('net', $data[1]);
			return true;
		}))->andReturn(false);
		$translator->shouldReceive('trans')->once()->with('validation.custom.foo.domain')->andReturn('validation.custom.foo.domain');
		$translator->shouldReceive('trans')->once()->with('validation.domain')->andReturn('validation.domain');
		$translator->shouldReceive('trans')->once()->with('validation.attributes.foo')->andReturn('validation.attributes.foo');

		$factory = new Factory($translator, $container);
		$factory->extend('domain', 'Hampel\Tlds\Validation\ValidatorExtensions@validateDomain', ':attribute must be a valid domain name');
		$validator = $factory->make(['foo' => 'example.invalid-tld'], ['foo' => 'domain']);
		$this->assertTrue($validator->fails());

		$messages = $validator->messages();
		$this->assertInstanceOf('Illuminate\Support\MessageBag', $messages);
		$this->assertEquals('foo must be a valid domain name', $messages->first('foo'));
	}

	public function testValidateDomainIn()
	{
		$validator = Mockery::mock('Hampel\Validate\Validator');
		$tlds = Mockery::mock('Hampel\Tlds\Tlds');

		$extensions = new ValidatorExtensions($validator, $tlds);

		$container = Mockery::mock('Illuminate\Container\Container');
		$translator = Mockery::mock('Symfony\Component\Translation\TranslatorInterface');

		$container->shouldReceive('make')->once()->with('Hampel\Tlds\Validation\ValidatorExtensions')->andReturn($extensions);
		$validator->shouldReceive('isDomain')->once()->with('example.com', Mockery::on(function($data)
		{
			$this->assertTrue(is_array($data));
			$this->assertEquals(3, count($data));
			$this->assertEquals('com', $data[0]);
			$this->assertEquals('net', $data[1]);
			return true;
		}))->andReturn(true);

		$factory = new Factory($translator, $container);
		$factory->extend('domain_in', 'Hampel\Tlds\Validation\ValidatorExtensions@validateDomainIn', ':attribute TLD must be one of :values');
		$validator = $factory->make(['foo' => 'example.com'], ['foo' => 'domain_in:com,net,co']);
		$this->assertTrue($validator->passes());
	}

	public function testValidateDomainInFails()
	{
		$validator = Mockery::mock('Hampel\Validate\Validator');
		$tlds = Mockery::mock('Hampel\Tlds\Tlds');

		$extensions = new ValidatorExtensions($validator, $tlds);

		$container = Mockery::mock('Illuminate\Container\Container');
		$translator = Mockery::mock('Symfony\Component\Translation\TranslatorInterface');

		$container->shouldReceive('make')->twice()->with('Hampel\Tlds\Validation\ValidatorExtensions')->andReturn($extensions);
		$validator->shouldReceive('isDomain')->once()->with('example.invalid-tld', Mockery::on(function($data)
		{
			$this->assertTrue(is_array($data));
			$this->assertEquals(3, count($data));
			$this->assertEquals('com', $data[0]);
			$this->assertEquals('net', $data[1]);
			return true;
		}))->andReturn(false);
		$translator->shouldReceive('trans')->once()->with('validation.custom.foo.domain_in')->andReturn('validation.custom.foo.domain_in');
		$translator->shouldReceive('trans')->once()->with('validation.domain_in')->andReturn('validation.domain_in');
		$translator->shouldReceive('trans')->once()->with('validation.attributes.foo')->andReturn('validation.attributes.foo');

		$factory = new Factory($translator, $container);
		$factory->extend('domain_in', 'Hampel\Tlds\Validation\ValidatorExtensions@validateDomainIn', ':attribute TLD must be one of :values');
		$factory->replacer('domain_in', 'Hampel\Tlds\Validation\ValidatorExtensions@replaceDomainIn');
		$validator = $factory->make(['foo' => 'example.invalid-tld'], ['foo' => 'domain_in:com,net,co']);
		$this->assertTrue($validator->fails());

		$messages = $validator->messages();
		$this->assertInstanceOf('Illuminate\Support\MessageBag', $messages);
		$this->assertEquals('foo TLD must be one of com, net, co', $messages->first('foo'));
	}

//	public function testValidateTld()
//	{
//		$validator = Mockery::mock('Hampel\Validate\Validator');
//		$tlds = Mockery::mock('Hampel\Tlds\Tlds');
//
//		$extensions = new ValidatorExtensions($validator, $tlds);
//
//		$container = Mockery::mock('Illuminate\Container\Container');
//		$translator = Mockery::mock('Symfony\Component\Translation\TranslatorInterface');
//
//		$container->shouldReceive('make')->once()->with('Hampel\Tlds\Validation\ValidatorExtensions')->andReturn($extensions);
//		$tlds->shouldReceive('get')->once()->with()->andReturn(['com', 'net', 'co']);
//		$validator->shouldReceive('isDomain')->once()->with('example.com', Mockery::on(function($data)
//		{
//			$this->assertTrue(is_array($data));
//			$this->assertEquals(3, count($data));
//			$this->assertEquals('com', $data[0]);
//			$this->assertEquals('net', $data[1]);
//			return true;
//		}))->andReturn(true);
//
//		$factory = new Factory($translator, $container);
//		$factory->extend('domain', 'Hampel\Tlds\Validation\ValidatorExtensions@validateDomain', ':attribute must be a valid domain name');
//		$validator = $factory->make(['foo' => 'example.com'], ['foo' => 'tld']);
//		$this->assertTrue($validator->passes());
//	}

//	public function testValidateTldPassesLive()
//	{
//		$lang = Mockery::mock('Illuminate\Translation\Translator');
//		$container = Mockery::mock('Illuminate\Container\Container');
//		$config = Mockery::mock('Illuminate\Config\Repository');
//		$validator = Mockery::mock('Hampel\Validate\Validator');
//		$tldcache = Mockery::mock('Hampel\Validate\Laravel\TldCache');
//
//		$container->shouldReceive('make')->once()->with('validate-laravel.validator')->andReturn($validator);
//		$container->shouldReceive('offsetGet')->once()->with('config')->andReturn($config);
//		$config->shouldReceive('get')->once()->with('validate-laravel::tld_live')->andReturn(true);
//		$container->shouldReceive('make')->once()->with('validate-laravel.tlds')->andReturn($tldcache);
//		$tldcache->shouldReceive('getTlds')->once()->andReturn(array('com', 'net', 'org'));
//		$validator->shouldReceive('isTld')->once()->with('bar.com', array('com', 'net', 'org'))->andReturn(true);
//
//		$v = new ValidatorClass($lang, array('foo' => 'bar.com'), array('foo' => 'tld'));
//		$v->setContainer($container);
//		$this->assertTrue($v->passes());
//	}
//
//	public function testValidateTldPassesLocal()
//	{
//		$lang = Mockery::mock('Illuminate\Translation\Translator');
//		$container = Mockery::mock('Illuminate\Container\Container');
//		$config = Mockery::mock('Illuminate\Config\Repository');
//		$validator = Mockery::mock('Hampel\Validate\Validator');
//		$tldcache = Mockery::mock('Hampel\Validate\Laravel\TldCache');
//
//		$container->shouldReceive('make')->once()->with('validate-laravel.validator')->andReturn($validator);
//		$container->shouldReceive('offsetGet')->once()->with('config')->andReturn($config);
//		$config->shouldReceive('get')->once()->with('validate-laravel::tld_live')->andReturn(false);
//		$validator->shouldReceive('getTlds')->once()->andReturn(array('com', 'net', 'org'));
//		$validator->shouldReceive('isTld')->once()->with('bar.com', array('com', 'net', 'org'))->andReturn(true);
//
//		$v = new ValidatorClass($lang, array('foo' => 'bar.com'), array('foo' => 'tld'));
//		$v->setContainer($container);
//		$this->assertTrue($v->passes());
//	}
//
//	public function testValidateTldFailsLive()
//	{
//		$lang = Mockery::mock('Illuminate\Translation\Translator');
//		$container = Mockery::mock('Illuminate\Container\Container');
//		$config = Mockery::mock('Illuminate\Config\Repository');
//		$validator = Mockery::mock('Hampel\Validate\Validator');
//		$tldcache = Mockery::mock('Hampel\Validate\Laravel\TldCache');
//
//		$container->shouldReceive('make')->once()->with('validate-laravel.validator')->andReturn($validator);
//		$container->shouldReceive('offsetGet')->once()->with('config')->andReturn($config);
//		$config->shouldReceive('get')->once()->with('validate-laravel::tld_live')->andReturn(true);
//		$container->shouldReceive('make')->once()->with('validate-laravel.tlds')->andReturn($tldcache);
//		$tldcache->shouldReceive('getTlds')->once()->andReturn(array('com', 'net', 'org'));
//		$validator->shouldReceive('isTld')->once()->with('bar.biz', array('com', 'net', 'org'))->andReturn(false);
//		$lang->shouldReceive('trans')->once()->with('validation.custom.foo.tld');
//		$lang->shouldReceive('trans')->once()->with('validation.attributes.foo');
//
//		$v = new ValidatorClass($lang, array('foo' => 'bar.biz'), array('foo' => 'tld'));
//		$v->setContainer($container);
//		$this->assertFalse($v->passes());
//	}
//
//	public function testValidateTldFailsLocal()
//	{
//		$lang = Mockery::mock('Illuminate\Translation\Translator');
//		$container = Mockery::mock('Illuminate\Container\Container');
//		$config = Mockery::mock('Illuminate\Config\Repository');
//		$validator = Mockery::mock('Hampel\Validate\Validator');
//		$tldcache = Mockery::mock('Hampel\Validate\Laravel\TldCache');
//
//		$container->shouldReceive('make')->once()->with('validate-laravel.validator')->andReturn($validator);
//		$container->shouldReceive('offsetGet')->once()->with('config')->andReturn($config);
//		$config->shouldReceive('get')->once()->with('validate-laravel::tld_live')->andReturn(false);
//		$validator->shouldReceive('getTlds')->once()->andReturn(array('com', 'net', 'org'));
//		$validator->shouldReceive('isTld')->once()->with('bar.biz', array('com', 'net', 'org'))->andReturn(false);
//		$lang->shouldReceive('trans')->once()->with('validation.custom.foo.tld');
//		$lang->shouldReceive('trans')->once()->with('validation.attributes.foo');
//
//		$v = new ValidatorClass($lang, array('foo' => 'bar.biz'), array('foo' => 'tld'));
//		$v->setContainer($container);
//		$this->assertFalse($v->passes());
//	}

	public function tearDown() {
		Mockery::close();
	}
}

?>
 