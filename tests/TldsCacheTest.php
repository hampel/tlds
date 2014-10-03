<?php namespace Hampel\Tlds;

use Mockery;

class TldsCacheTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->config = Mockery::mock('Illuminate\Contracts\Config\Repository');
		$this->cache = Mockery::mock('Illuminate\Contracts\Cache\Repository');
		$this->log = Mockery::mock('Illuminate\Contracts\Logging\Log');
		$this->filesystem = Mockery::mock('Illuminate\Contracts\Filesystem\Filesystem');
	}

	public function testCache()
	{
		$this->config->shouldReceive('get')->once()->with('tlds::cache.key')->andReturn('tlds');
		$this->config->shouldReceive('get')->once()->with('tlds::cache.expiry')->andReturn(1440);
		$this->cache->shouldReceive('remember')->once()->with('tlds', 1440, Mockery::on(function($closure)
		{
			$this->config->shouldReceive('get')->once()->with('tlds::source.type')->andReturn('filesystem');
			$this->config->shouldReceive('get')->once()->with('tlds::source.path')->andReturn('tlds.txt');
			$this->log->shouldReceive('info')->once()->with('Fetching updated TLDs from Filesystem: tlds.txt');
			$this->filesystem->shouldReceive('get')->once()->with('tlds.txt')->andReturn(file_get_contents(__DIR__ . '/mocks/tlds-alpha-by-domain.txt'));
			$this->log->shouldReceive('info')->once()->with('Added 725 TLDs to cache');

			$tlds = $closure();

			$this->assertTrue(is_array($tlds));
			$this->assertTrue(count($tlds) == 725);
			$this->assertTrue(in_array('com', $tlds));
			$this->assertTrue(in_array('au', $tlds));
			$this->assertTrue(in_array('xn--zfr164b', $tlds));

			return true;
		}))->andReturn('foo');

		$tlds = (new Tlds($this->config, $this->cache, $this->log, $this->filesystem, null))->get();

		$this->assertEquals('foo', $tlds);
	}

	public function tearDown() {
		Mockery::close();
	}
}

?>
