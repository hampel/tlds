<?php namespace Hampel\Tlds\Tests\Console;

use Hampel\Tlds\Exceptions\FetchException;
use Hampel\Tlds\Fetcher\TldFetcher;
use Hampel\Tlds\Tests\UnitTestCase;
use Illuminate\Support\Facades\Cache;
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

    public function testUpdateReturnsErrorOnNoData()
    {
        $this->mock(TldFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchTlds')->once()->andReturn('');
        });

        Cache::shouldReceive('forget')->once()->with('cache-key');

        $this->artisan('tld:update')
            ->expectsOutputToContain('No data returned from TLD fetch')
            ->assertFailed();
    }

    public function testUpdateReturnsErrorOnFetchFailure()
    {
        $this->mock(TldFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchTlds')->once()->andThrow(new FetchException('exception-message'));
        });

        Cache::shouldReceive('forget')->once()->with('cache-key');

        $this->artisan('tld:update')
            ->expectsOutputToContain('exception-message')
            ->assertFailed();
    }

    public function testUpdateConsoleSucceeds()
    {
        $data = file_get_contents(__DIR__ . '/../mock/tlds-alpha-by-domain.txt');

        Cache::shouldReceive('forget')->once()->with('cache-key');
        Cache::shouldReceive('put')->once()->with('cache-key', \Mockery::type('array'), '66');

        $this->mock(TldFetcher::class, function (MockInterface $mock) use ($data) {
            $mock->shouldReceive('fetchTlds')->once()->andReturn($data);
        });

        $this->artisan('tld:update')->assertSuccessful();
    }

    public function testUpdateReportsSkippedLines()
    {
        $data = file_get_contents(__DIR__ . '/../mock/filter-test.txt');

        Cache::shouldReceive('forget')->once()->with('cache-key');
        Cache::shouldReceive('put')->once()->with('cache-key', \Mockery::type('array'), '66');

        $this->mock(TldFetcher::class, function (MockInterface $mock) use ($data) {
            $mock->shouldReceive('fetchTlds')->once()->andReturn($data);
        });

        $this->artisan('tld:update')
            ->expectsOutputToContain('Skipped [123] - did not match the TLD format')
            ->expectsOutputToContain('Skipped [test@example.com] - did not match the TLD format')
            ->assertSuccessful();
    }
}
