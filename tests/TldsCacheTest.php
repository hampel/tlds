<?php namespace Hampel\Tlds;

use Mockery;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Hampel\Tlds\Fetcher\TldFetcher;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;

class TldsCacheTest extends TestCase
{
	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	public function setUp() : void
	{
		$this->config = Mockery::mock(Config::class);
		$this->cache = Mockery::mock(Cache::class);
		$this->log = Mockery::mock(LoggerInterface::class);
		$this->fetcher = Mockery::mock(TldFetcher::class);
	}

	public function testCache()
	{
		$this->config->shouldReceive('get')->once()->with('tlds.cache.key')->andReturn('tlds');
		$this->config->shouldReceive('get')->once()->with('tlds.cache.expiry')->andReturn(1440);
		$this->cache->shouldReceive('remember')->once()->with('tlds', 1440, Mockery::on(function($closure)
		{
//			$this->config->shouldReceive('get')->once()->with('tlds.source')->andReturn('filesystem');
//			$this->config->shouldReceive('get')->once()->with('tlds.path')->andReturn('tlds.txt');
//			$this->log->shouldReceive('info')->once()->with('Fetching updated TLDs from Filesystem: tlds.txt');
			$this->fetcher->shouldReceive('fetchTlds')->once()->andReturn(file_get_contents(
				__DIR__ . '/mock/tlds-alpha-by-domain.txt'
			));
			$this->log->shouldReceive('info')->once()->with('Added 725 TLDs to cache');

			$tlds = $closure();

			$this->assertTrue(is_array($tlds));
			$this->assertTrue(count($tlds) == 725);
			$this->assertTrue(in_array('com', $tlds));
			$this->assertTrue(in_array('au', $tlds));
			$this->assertTrue(in_array('xn--zfr164b', $tlds));

			return true;
		}))->andReturn('foo');

		$tlds = (new Tlds($this->config, $this->cache, $this->log, $this->fetcher))->get();

		$this->assertEquals('foo', $tlds);
	}
}
