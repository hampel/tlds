<?php namespace Hampel\Tlds;

use Mockery;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;

class TldsUrlTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->mock = new MockHandler();

		$this->client = new Client(['base_uri' => 'http://example.com', 'handler' => $this->mock]);
	}

	protected function loadMockResponse($filename)
	{
		return \GuzzleHttp\Psr7\parse_response($this->loadMockData($filename));
	}

	protected function loadMockData($filename)
	{
		return file_get_contents($this->getMockPath() . $filename);
	}

	protected function getMockPath()
	{
		return dirname(__FILE__) . DIRECTORY_SEPARATOR . "mock" . DIRECTORY_SEPARATOR;
	}

	public function testRefreshGuzzleNotInitialised()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');

		$config->shouldReceive('get')->once()->with('tlds.source.type')->andReturn('url');
		$config->shouldReceive('get')->once()->with('tlds.source.url')->andReturn('http://foo.example.com/bar.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from URL: http://foo.example.com/bar.txt');

		$this->setExpectedException('Hampel\Tlds\Exceptions\ServiceProviderException', 'Guzzle client not initialised');

		$tlds = (new Tlds($config, $cache, $log, null, null))->fresh();
	}

	public function testRefreshGuzzleRequestException()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');
		$this->mock->append(new RequestException('foo', new Request('GET', 'http://example.com')));

		$config->shouldReceive('get')->once()->with('tlds.source.type')->andReturn('url');
		$config->shouldReceive('get')->once()->with('tlds.source.url')->andReturn('http://foo.example.com/bar.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from URL: http://foo.example.com/bar.txt');

		$this->setExpectedException('Hampel\Tlds\Exceptions\HttpException', 'foo');

		$tlds = (new Tlds($config, $cache, $log, null, $this->client))->fresh();
	}

	public function testRefreshGuzzleNoContent()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');

		$this->mock->append(new Response(204));

		$config->shouldReceive('get')->once()->with('tlds.source.type')->andReturn('url');
		$config->shouldReceive('get')->once()->with('tlds.source.url')->andReturn('http://foo.example.com/bar.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from URL: http://foo.example.com/bar.txt');

		$this->setExpectedException('Hampel\Tlds\Exceptions\BadResponseException', 'No data returned when fetching TLDs from URL http://foo.example.com/bar.txt');

		$tlds = (new Tlds($config, $cache, $log, null, $this->client))->fresh();
	}

	public function testRefreshGuzzleEmptyResponse()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');

		$this->mock->append($this->loadMockResponse('empty.txt'));

		$config->shouldReceive('get')->once()->with('tlds.source.type')->andReturn('url');
		$config->shouldReceive('get')->once()->with('tlds.source.url')->andReturn('http://foo.example.com/bar.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from URL: http://foo.example.com/bar.txt');

		$this->setExpectedException('Hampel\Tlds\Exceptions\BadResponseException', 'No data returned when fetching TLDs from URL http://foo.example.com/bar.txt');

		$tlds = (new Tlds($config, $cache, $log, null, $this->client))->fresh();
	}

	public function testRefreshGuzzle()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');

		$this->mock->append($this->loadMockResponse('tlds-guzzle.txt'));

		$config->shouldReceive('get')->once()->with('tlds.source.type')->andReturn('url');
		$config->shouldReceive('get')->once()->with('tlds.source.url')->andReturn('http://foo.example.com/bar.txt');
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
