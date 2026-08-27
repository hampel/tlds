<?php namespace Hampel\Tlds\Tests\Console;

use Hampel\Tlds\Exceptions\FetchException;
use Hampel\Tlds\Fetcher\TldFetcher;
use Hampel\Tlds\Tests\UnitTestCase;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Testing\PendingCommand;
use Mockery\MockInterface;

class UpdateTldsTest extends UnitTestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = $app->make(Config::class);

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
        /** @var Application $app */
        $app = $this->app;

        /** @var string|null $store */
        $store = $app->make(Config::class)->get('tlds.cache.store');

        return $app->make(CacheFactory::class)->store($store);
    }

    /**
     * The command under test. Wrapped because artisan() is typed PendingCommand|int
     * and only the object form is ever returned when assertions follow.
     */
    protected function tldUpdate(): PendingCommand
    {
        /** @var PendingCommand $command */
        $command = $this->artisan('tld:update');

        return $command;
    }

    protected function fixture(string $name): string
    {
        $data = file_get_contents(__DIR__ . '/../mock/' . $name);
        $this->assertIsString($data, "could not read fixture {$name}");

        return $data;
    }

    protected function fetcherReturns(string $data): void
    {
        $this->mock(TldFetcher::class, function (MockInterface $mock) use ($data) {
            $mock->shouldReceive('fetchTlds')->once()->andReturn($data);
        });
    }

    public function testUpdateReturnsErrorOnNoData(): void
    {
        $this->fetcherReturns('');

        $this->tldUpdate()
            ->expectsOutputToContain('No data returned from TLD fetch')
            ->assertFailed();
    }

    public function testUpdateReturnsErrorOnFetchFailure(): void
    {
        $this->mock(TldFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchTlds')->once()->andThrow(new FetchException('exception-message'));
        });

        $this->tldUpdate()
            ->expectsOutputToContain('exception-message')
            ->assertFailed();
    }

    public function testUpdateClearsStaleDataEvenWhenTheFetchFails(): void
    {
        $this->cache()->put('cache-key', ['stale'], 66);

        $this->mock(TldFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchTlds')->once()->andThrow(new FetchException('exception-message'));
        });

        $this->tldUpdate()->assertFailed();

        $this->assertNull($this->cache()->get('cache-key'));
    }

    public function testUpdateConsoleSucceeds(): void
    {
        $this->fetcherReturns($this->fixture('tlds-alpha-by-domain.txt'));

        $this->tldUpdate()
            ->expectsOutputToContain('Added 725 TLDs to the cache')
            ->assertSuccessful();

        /** @var array<int, string> $cached */
        $cached = $this->cache()->get('cache-key');
        $this->assertSame(725, count($cached));
    }

    public function testUpdateReportsSkippedLines(): void
    {
        $this->fetcherReturns($this->fixture('filter-test.txt'));

        $this->tldUpdate()
            ->expectsOutputToContain('Skipped [123] - did not match the TLD format')
            ->expectsOutputToContain('Skipped [test@example.com] - did not match the TLD format')
            ->assertSuccessful();
    }
}
