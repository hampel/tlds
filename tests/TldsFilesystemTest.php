<?php namespace Hampel\Tlds;

use Mockery;

class TldsFilesystemTest extends \PHPUnit_Framework_TestCase
{
	public function testRefreshFilesystemNotInitialised()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');

		$config->shouldReceive('get')->once()->with('tlds.source.type')->andReturn('filesystem');
		$config->shouldReceive('get')->once()->with('tlds.source.path')->andReturn('tlds.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from Filesystem: tlds.txt');

		$this->setExpectedException('Hampel\Tlds\Exceptions\ServiceProviderException', 'Filesystem not initialised');

		$tlds = (new Tlds($config, $cache, $log, null, null))->fresh();
	}

	public function testRefreshFilesystemFileNotFound()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');
		$filesystem = Mockery::mock('Illuminate\Contracts\Filesystem\Filesystem');

		$config->shouldReceive('get')->once()->with('tlds.source.type')->andReturn('filesystem');
		$config->shouldReceive('get')->once()->with('tlds.source.path')->andReturn('tlds.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from Filesystem: tlds.txt');
		$filesystem->shouldReceive('get')->once()->with('tlds.txt')->andThrow('Illuminate\Contracts\Filesystem\FileNotFoundException', 'foo');

		$this->setExpectedException('Hampel\Tlds\Exceptions\FilesystemException', 'foo');

		$tlds = (new Tlds($config, $cache, $log, $filesystem, null))->fresh();
	}

	public function testRefreshFilesystemEmptyResponse()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');
		$filesystem = Mockery::mock('Illuminate\Contracts\Filesystem\Filesystem');

		$config->shouldReceive('get')->once()->with('tlds.source.type')->andReturn('filesystem');
		$config->shouldReceive('get')->once()->with('tlds.source.path')->andReturn('tlds.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from Filesystem: tlds.txt');
		$filesystem->shouldReceive('get')->once()->with('tlds.txt')->andReturn('');

		$this->setExpectedException('Hampel\Tlds\Exceptions\BadResponseException', 'No data returned when fetching TLDs from Filesystem tlds.txt');

		$tlds = (new Tlds($config, $cache, $log, $filesystem, null))->fresh();
	}

	public function testRefreshFilesystemBadTlds()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');
		$filesystem = Mockery::mock('Illuminate\Contracts\Filesystem\Filesystem');

		$config->shouldReceive('get')->once()->with('tlds.source.type')->andReturn('filesystem');
		$config->shouldReceive('get')->once()->with('tlds.source.path')->andReturn('tlds.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from Filesystem: tlds.txt');
		$filesystem->shouldReceive('get')->once()->with('tlds.txt')->andReturn(file_get_contents(
																			   __DIR__ . '/mock/bad-tlds.txt'
																			   ));

		$log->shouldReceive('warning')->once()->with('Skipped TLD [not a valid tld] - did not match regex validator');
		$log->shouldReceive('warning')->once()->with('Skipped TLD [alsonotavalidtld!] - did not match regex validator');
		$log->shouldReceive('warning')->once()->with('Skipped TLD [xn--] - did not match regex validator');
		$log->shouldReceive('warning')->once()->with('Skipped TLD [a] - did not match regex validator');
		$log->shouldReceive('warning')->once()->with('Skipped TLD [com2] - did not match regex validator');

		$log->shouldReceive('info')->once()->with('Added 0 TLDs to cache');

		$tlds = (new Tlds($config, $cache, $log, $filesystem, null))->fresh();

		$this->assertEmpty($tlds);
	}

	public function testRefreshFilesystem()
	{
		$config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$log = Mockery::mock('Illuminate\Contracts\Logging\Log');
		$filesystem = Mockery::mock('Illuminate\Contracts\Filesystem\Filesystem');

		$config->shouldReceive('get')->once()->with('tlds.source.type')->andReturn('filesystem');
		$config->shouldReceive('get')->once()->with('tlds.source.path')->andReturn('tlds.txt');
		$log->shouldReceive('info')->once()->with('Fetching updated TLDs from Filesystem: tlds.txt');
		$filesystem->shouldReceive('get')->once()->with('tlds.txt')->andReturn(file_get_contents(
																			   __DIR__ . '/mock/tlds-alpha-by-domain.txt'
																			   ));

		$log->shouldReceive('info')->once()->with('Added 725 TLDs to cache');

		$tlds = (new Tlds($config, $cache, $log, $filesystem, null))->fresh();

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
