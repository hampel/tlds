<?php namespace Hampel\Tlds\Tests\Console;

use Hampel\Tlds\Exceptions\FetchException;
use Hampel\Tlds\Fetcher\TldFetcher;
use Hampel\Tlds\Tests\UnitTestCase;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Mockery\MockInterface;

class UpdateTldsTest extends UnitTestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = $app->get('config');

        $config->set('tlds.cache.key', 'cache-key');
        $config->set('tlds.cache.expiry', '66');
    }

    /**
     * The store the command will actually have written to - resolved the same way
     * the service provider resolves it, so the assertions follow the configuration
     * rather than assuming a default.
     */
    protected function cache(): Repository
    {
        return $this->app->make(CacheFactory::class)->store(config('tlds.cache.store'));
    }

    protected function fetcherReturns(string $data): void
    {
        $this->mock(TldFetcher::class, function (MockInterface $mock) use ($data) {
            $mock->shouldReceive('fetchTlds')->once()->andReturn($data);
        });
    }

    public function testUpdateReturnsErrorOnNoData()
    {
        $this->fetcherReturns('');

        $this->artisan('tld:update')
            ->expectsOutputToContain('No data returned from TLD fetch')
            ->assertFailed();
    }

    public function testUpdateReturnsErrorOnFetchFailure()
    {
        $this->mock(TldFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchTlds')->once()->andThrow(new FetchException('exception-message'));
        });

        $this->artisan('tld:update')
            ->expectsOutputToContain('exception-message')
            ->assertFailed();
    }

    public function testUpdateClearsStaleDataEvenWhenTheFetchFails()
    {
        $this->cache()->put('cache-key', ['stale'], 66);

        $this->mock(TldFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchTlds')->once()->andThrow(new FetchException('exception-message'));
        });

        $this->artisan('tld:update')->assertFailed();

        $this->assertNull($this->cache()->get('cache-key'));
    }

    public function testUpdateConsoleSucceeds()
    {
        $this->fetcherReturns(file_get_contents(__DIR__ . '/../mock/tlds-alpha-by-domain.txt'));

        $this->artisan('tld:update')
            ->expectsOutputToContain('Added 725 TLDs to the cache')
            ->assertSuccessful();

        $this->assertEquals(725, count($this->cache()->get('cache-key')));
    }

    public function testUpdateReportsSkippedLines()
    {
        $this->fetcherReturns(file_get_contents(__DIR__ . '/../mock/filter-test.txt'));

        $this->artisan('tld:update')
            ->expectsOutputToContain('Skipped [123] - did not match the TLD format')
            ->expectsOutputToContain('Skipped [test@example.com] - did not match the TLD format')
            ->assertSuccessful();
    }
}
