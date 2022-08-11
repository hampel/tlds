<?php namespace Hampel\Tlds;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hampel\Tlds\Exceptions\BadResponseException;
use Hampel\Tlds\Exceptions\HttpException;
use Hampel\Tlds\Fetcher\UrlTldFetcher;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TldsUrlTest extends TestCase
{
	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	public function setUp() : void
	{
		$this->mock = new MockHandler();

		$this->client = new Client(['base_uri' => 'http://example.com', 'handler' => $this->mock]);
	}

	protected function loadMockResponse($filename)
	{
		return Message::parseResponse($this->loadMockData($filename));
	}

	protected function loadMockData($filename)
	{
		return file_get_contents($this->getMockPath() . $filename);
	}

	protected function getMockPath()
	{
		return dirname(__FILE__) . DIRECTORY_SEPARATOR . "mock" . DIRECTORY_SEPARATOR;
	}

	public function testRefreshGuzzleRequestException()
	{
		$config = Mockery::mock(Config::class);
		$cache = Mockery::mock(Cache::class);
		$log = Mockery::mock(LoggerInterface::class);
		$this->mock->append(new RequestException('foo', new Request('GET', 'http://example.com')));

		$config->shouldReceive('get')->once()->with('tlds.url')->andReturn('http://foo.example.com/bar.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from URL: http://foo.example.com/bar.txt');

		$this->expectException(HttpException::class);
		$this->expectExceptionMessage('foo');

		$fetcher = new UrlTldFetcher($this->client, $config, $log);

		$tlds = (new Tlds($config, $cache, $log, $fetcher))->fresh();
	}

	public function testRefreshGuzzleNoContent()
	{
		$config = Mockery::mock(Config::class);
		$cache = Mockery::mock(Cache::class);
		$log = Mockery::mock(LoggerInterface::class);

		$this->mock->append(new Response(204));

		$config->shouldReceive('get')->once()->with('tlds.url')->andReturn('http://foo.example.com/bar.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from URL: http://foo.example.com/bar.txt');

		$this->expectException(BadResponseException::class);
		$this->expectExceptionMessage('No data returned when fetching TLDs from URL http://foo.example.com/bar.txt');

		$fetcher = new UrlTldFetcher($this->client, $config, $log);

		$tlds = (new Tlds($config, $cache, $log, $fetcher))->fresh();
	}

	public function testRefreshGuzzleEmptyResponse()
	{
		$config = Mockery::mock(Config::class);
		$cache = Mockery::mock(Cache::class);
		$log = Mockery::mock(LoggerInterface::class);

		$this->mock->append($this->loadMockResponse('empty.txt'));

		$config->shouldReceive('get')->once()->with('tlds.url')->andReturn('http://foo.example.com/bar.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from URL: http://foo.example.com/bar.txt');

		$this->expectException(BadResponseException::class);
		$this->expectExceptionMessage('No data returned when fetching TLDs from URL http://foo.example.com/bar.txt');

		$fetcher = new UrlTldFetcher($this->client, $config, $log);

		$tlds = (new Tlds($config, $cache, $log, $fetcher))->fresh();
	}

	public function testRefreshGuzzle()
	{
		$config = Mockery::mock(Config::class);
		$cache = Mockery::mock(Cache::class);
		$log = Mockery::mock(LoggerInterface::class);

		$this->mock->append($this->loadMockResponse('tlds-guzzle.txt'));

		$config->shouldReceive('get')->once()->with('tlds.url')->andReturn('http://foo.example.com/bar.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from URL: http://foo.example.com/bar.txt');

		$log->shouldReceive('info')->once()->with('Added 725 TLDs to cache');

		$fetcher = new UrlTldFetcher($this->client, $config, $log);

		$tlds = (new Tlds($config, $cache, $log, $fetcher))->fresh();

		$this->assertTrue(is_array($tlds));
		$this->assertTrue(count($tlds) == 725);
		$this->assertTrue(in_array('com', $tlds));
		$this->assertTrue(in_array('au', $tlds));
		$this->assertTrue(in_array('xn--zfr164b', $tlds));
	}
}
