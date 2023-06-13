<?php namespace Hampel\Tlds\Tests\Console;

use Hampel\Tlds\Exceptions\FetchException;
use Hampel\Tlds\Fetcher\TldFetcher;
use Hampel\Tlds\Tests\UnitTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

        $config->set('tlds.source', 'filesystem');
        $config->set('tlds.cache.key', 'cache-key');
        $config->set('tlds.cache.expiry', '66');
    }

    public function testUpdateReturnsErrorOnNoData()
    {
        $this->mock(TldFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchTlds')->once()->andReturn('');
        });

        Cache::shouldReceive('forget')->once()->with('cache-key');

        Log::shouldReceive('error')
            ->once()
            ->with(
                'No data returned from TLD fetch',
                [
                    'code' => 0,
                    'exception' => 'Hampel\Tlds\Exceptions\NoDataException'
                ]
            );

        $this->artisan('tld:update')->assertFailed();
    }

    public function testUpdateReturnsErrorOnFetchFailure()
    {
        $this->mock(TldFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchTlds')->once()->andThrow(new FetchException('exception-message'));
        });

        Cache::shouldReceive('forget')->once()->with('cache-key');

        Log::shouldReceive('error')
            ->once()
            ->with(
                'exception-message',
                [
                    'code' => 0,
                    'exception' => 'Hampel\Tlds\Exceptions\FetchException'
                ]
            );

        $this->artisan('tld:update')->assertFailed();
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
}
