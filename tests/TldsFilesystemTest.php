<?php namespace Hampel\Tlds;

use Mockery;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Hampel\Tlds\Fetcher\FilesystemTldFetcher;
use Illuminate\Contracts\Filesystem\Filesystem;
use Hampel\Tlds\Exceptions\FilesystemException;
use Hampel\Tlds\Exceptions\BadResponseException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class TldsFilesystemTest extends TestCase
{
	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	public function testRefreshFilesystemFileNotFound()
	{
		$config = Mockery::mock(Config::class);
		$cache = Mockery::mock(Cache::class);
		$log = Mockery::mock(LoggerInterface::class);
		$filesystem = Mockery::mock(Filesystem::class);

		$config->shouldReceive('get')->once()->with('tlds.path')->andReturn('tlds.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from Filesystem: tlds.txt');
		$filesystem->shouldReceive('get')->once()->with('tlds.txt')->andThrow(FileNotFoundException::class, 'foo');

		$this->expectException(FilesystemException::class, 'foo');

		$fetcher = new FilesystemTldFetcher($filesystem, $config, $log);

		$tlds = (new Tlds($config, $cache, $log, $fetcher))->fresh();
	}

	public function testRefreshFilesystemEmptyResponse()
	{
		$config = Mockery::mock(Config::class);
		$cache = Mockery::mock(Cache::class);
		$log = Mockery::mock(LoggerInterface::class);
		$filesystem = Mockery::mock(Filesystem::class);

		$config->shouldReceive('get')->once()->with('tlds.path')->andReturn('tlds.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from Filesystem: tlds.txt');
		$filesystem->shouldReceive('get')->once()->with('tlds.txt')->andReturn('');

		$this->expectException(BadResponseException::class, 'No data returned when fetching TLDs from Filesystem tlds.txt');

		$fetcher = new FilesystemTldFetcher($filesystem, $config, $log);

		$tlds = (new Tlds($config, $cache, $log, $fetcher))->fresh();
	}

	public function testRefreshFilesystemBadTlds()
	{
		$config = Mockery::mock(Config::class);
		$cache = Mockery::mock(Cache::class);
		$log = Mockery::mock(LoggerInterface::class);
		$filesystem = Mockery::mock(Filesystem::class);

		$config->shouldReceive('get')->once()->with('tlds.path')->andReturn('tlds.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from Filesystem: tlds.txt');
		$filesystem->shouldReceive('get')->once()->with('tlds.txt')->andReturn(
			file_get_contents(__DIR__ . '/mock/bad-tlds.txt')
		);

		$log->shouldReceive('warning')->once()->with('Skipped TLD [not a valid tld] - did not match regex validator');
		$log->shouldReceive('warning')->once()->with('Skipped TLD [alsonotavalidtld!] - did not match regex validator');
		$log->shouldReceive('warning')->once()->with('Skipped TLD [xn--] - did not match regex validator');
		$log->shouldReceive('warning')->once()->with('Skipped TLD [a] - did not match regex validator');
		$log->shouldReceive('warning')->once()->with('Skipped TLD [com2] - did not match regex validator');

		$log->shouldReceive('info')->once()->with('Added 0 TLDs to cache');

		$fetcher = new FilesystemTldFetcher($filesystem, $config, $log);

		$tlds = (new Tlds($config, $cache, $log, $fetcher))->fresh();

		$this->assertEmpty($tlds);
	}

	public function testRefreshFilesystem()
	{
		$config = Mockery::mock(Config::class);
		$cache = Mockery::mock(Cache::class);
		$log = Mockery::mock(LoggerInterface::class);
		$filesystem = Mockery::mock(Filesystem::class);

		$config->shouldReceive('get')->once()->with('tlds.path')->andReturn('tlds.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from Filesystem: tlds.txt');
		$filesystem->shouldReceive('get')->once()->with('tlds.txt')->andReturn(
			file_get_contents(__DIR__ . '/mock/tlds-alpha-by-domain.txt')
		);

		$log->shouldReceive('info')->once()->with('Added 725 TLDs to cache');

		$fetcher = new FilesystemTldFetcher($filesystem, $config, $log);

		$tlds = (new Tlds($config, $cache, $log, $fetcher))->fresh();

		$this->assertTrue(is_array($tlds));
		$this->assertTrue(count($tlds) == 725);
		$this->assertTrue(in_array('com', $tlds));
		$this->assertTrue(in_array('au', $tlds));
		$this->assertTrue(in_array('xn--zfr164b', $tlds));
	}
}
