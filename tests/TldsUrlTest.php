<?php namespace Hampel\Tlds;

use Mockery;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Exception\RequestException;

class TldsUrlTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->mock = new Mock();

		$this->client = new Client();
		$this->client->getEmitter()->attach($this->mock);
	}

	public function testRefreshGuzzleNotInitialised()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');

		$config->shouldReceive('get')->once()->with('tlds::source.type')->andReturn('url');
		$config->shouldReceive('get')->once()->with('tlds::source.url')->andReturn('http://foo.example.com/bar.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from URL: http://foo.example.com/bar.txt');

		$this->setExpectedException('Hampel\Tlds\Exceptions\ServiceProviderException', 'Guzzle client not initialised');

		$tlds = (new Tlds($config, $cache, $log, null, null))->fresh();
	}

	public function testRefreshGuzzleRequestException()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');
		$this->mock->addException(new RequestException('foo', new Request('GET', 'http://example.com')));

		$config->shouldReceive('get')->once()->with('tlds::source.type')->andReturn('url');
		$config->shouldReceive('get')->once()->with('tlds::source.url')->andReturn('http://foo.example.com/bar.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from URL: http://foo.example.com/bar.txt');

		$this->setExpectedException('Hampel\Tlds\Exceptions\HttpException', 'foo');

		$tlds = (new Tlds($config, $cache, $log, null, $this->client))->fresh();
	}

	public function testRefreshGuzzleEmptyResponse()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');
		$this->mock->addResponse(__DIR__ . '/mocks/empty.txt');

		$config->shouldReceive('get')->once()->with('tlds::source.type')->andReturn('url');
		$config->shouldReceive('get')->once()->with('tlds::source.url')->andReturn('http://foo.example.com/bar.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from URL: http://foo.example.com/bar.txt');

		$this->setExpectedException('Hampel\Tlds\Exceptions\BadResponseException', 'No data returned when fetching TLDs from URL http://foo.example.com/bar.txt');

		$tlds = (new Tlds($config, $cache, $log, null, $this->client))->fresh();
	}

	public function testRefreshGuzzle()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');
		$this->mock->addResponse(__DIR__ . '/mocks/tlds-guzzle.txt');

		$config->shouldReceive('get')->once()->with('tlds::source.type')->andReturn('url');
		$config->shouldReceive('get')->once()->with('tlds::source.url')->andReturn('http://foo.example.com/bar.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from URL: http://foo.example.com/bar.txt');

		$log->shouldReceive('info')->once()->with('Added 725 TLDs to cache');

		$tlds = (new Tlds($config, $cache, $log, null, $this->client))->fresh();

		$this->assertTrue(is_array($tlds));
		$this->assertTrue(count($tlds) == 725);
		$this->assertTrue(in_array('com', $tlds));
		$this->assertTrue(in_array('au', $tlds));
		$this->assertTrue(in_array('xn--zfr164b', $tlds));
	}

	public function tearDown() {
		Mockery::close();
	}
}

?>
