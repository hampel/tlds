<?php namespace Hampel\Tlds\Tests\Fetcher;

use Hampel\Tlds\Exceptions\FilesystemException;
use Hampel\Tlds\Fetcher\FilesystemTldFetcher;
use Hampel\Tlds\Tests\UnitTestCase;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToReadFile;

class FilesystemTldFetcherTest extends UnitTestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = $app->get('config');

        $config->set('tlds.source', 'filesystem');
        $config->set('tlds.disk', null);
        $config->set('tlds.path', 'tlds.txt');
    }

    public function testfetchTldsFileNotFoundThrowsException()
    {
        Storage::fake();
        Storage::assertMissing('tlds.txt');

        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage("No file found at tlds.txt");

        $fetcher = $this->app->make(FilesystemTldFetcher::class);
        $fetcher->fetchTlds();
    }

    public function testfetchTldsFileUnreadableThrowsException()
    {
        Storage::shouldReceive('disk->exists')->once()->andReturn(true);
        Storage::shouldReceive('disk->get')->once()->andThrow(UnableToReadFile::class, 'exception-message', 99);

        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage("exception-message");
        $this->expectExceptionCode(99);

        $fetcher = $this->app->make(FilesystemTldFetcher::class);
        $fetcher->fetchTlds();
    }

    public function testfetchTldsDataReturned()
    {
        Storage::fake();
        Storage::put('tlds.txt', 'foo');
        Storage::assertExists('tlds.txt');

        $fetcher = $this->app->make(FilesystemTldFetcher::class);
        $this->assertEquals('foo', $fetcher->fetchTlds());
    }
}
