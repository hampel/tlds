<?php namespace Hampel\Tlds\Tests;

use Hampel\Tlds\Exceptions\NoDataException;
use Hampel\Tlds\Fetcher\TldFetcher;
use Hampel\Tlds\TldManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery as m;
use Mockery\MockInterface;

class TldManagerTest extends UnitTestCase
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

    public function testGetReturnsCachedData()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('cache-key', '66', m::on(function ($closure) {
                $this->mock(TldFetcher::class, function (MockInterface $mock) {
                    $mock->shouldReceive('fetchTlds')->never();
                });

                return true;
            }))
            ->andReturn(['foo']);

        $tldManager = $this->app->make(TldManager::class);
        $tlds = $tldManager->get();

        $this->assertIsArray($tlds);
        $this->assertEquals(['foo'], $tlds);
    }

    public function testFreshWithNoDataThrowsException()
    {
        $this->mock(TldFetcher::class, function (MockInterface $mock) {
            $mock->shouldReceive('fetchTlds')->once()->andReturn('');
        });

        $this->expectException(NoDataException::class);
        $this->expectExceptionMessage("No data returned from TLD fetch");

        $tldManager = $this->app->make(TldManager::class);
        $this->assertEmpty($tldManager->fresh());
    }

    public function testFreshWithBadTldsThrowsException()
    {
        $this->mock(TldFetcher::class, function (MockInterface $mock) {
            $data = file_get_contents(__DIR__ . '/mock/bad-tlds.txt');
            $mock->shouldReceive('fetchTlds')->once()->andReturn($data);
        });

        $this->expectException(NoDataException::class);
        $this->expectExceptionMessage("No data returned from TLD fetch");

        Log::shouldReceive('error')->never();
        Log::shouldReceive('warning')->withArgs(function ($arg) {
            $messages = [
                '[TLDs] Skipped [not a valid tld] - did not match regex validator',
                '[TLDs] Skipped [alsonotavalidtld!] - did not match regex validator',
                '[TLDs] Skipped [xn--] - did not match regex validator',
                '[TLDs] Skipped [a] - did not match regex validator',
                '[TLDs] Skipped [com2] - did not match regex validator'
            ];

            if (in_array($arg, $messages)) return true;
            return false;
        });

        $tldManager = $this->app->make(TldManager::class);
        $tlds = $tldManager->fresh();

        $this->assertIsArray($tlds);
        $this->assertEmpty($tlds);
    }

    public function testFreshWithValidTldsReturnsArray()
    {
        $this->mock(TldFetcher::class, function (MockInterface $mock) {
            $data = file_get_contents(__DIR__ . '/mock/tlds-alpha-by-domain.txt');
            $mock->shouldReceive('fetchTlds')->once()->andReturn($data);
        });

        Log::shouldReceive('warning')->never();

        $tldManager = $this->app->make(TldManager::class);
        $tlds = $tldManager->fresh();

        $this->assertIsArray($tlds);
        $this->assertEquals(725, count($tlds));
    }

    public function testProcessWithNoDataReturnsEmptyArray()
    {
        $tldManager = $this->app->make(TldManager::class);
        $tlds = $tldManager->process('');

        $this->assertIsArray($tlds);
        $this->assertEmpty($tlds);
    }

    public function testProcessReturnsFilteredData()
    {
        $data = file_get_contents(__DIR__ . '/mock/filter-test.txt');

        Log::shouldReceive('warning')->once()->with('[TLDs] Skipped [123] - did not match regex validator');
        Log::shouldReceive('warning')->once()->with('[TLDs] Skipped [test@example.com] - did not match regex validator');

        $tldManager = $this->app->make(TldManager::class);
        $tlds = $tldManager->process($data);

        $this->assertIsArray($tlds);
        $this->assertEquals(['academy', 'accountants', 'xn--1qqw23a', 'xn--3bst00m'], $tlds);
    }

    public function testForgetForgetsCacheKey()
    {
        Cache::shouldReceive('forget')->with('cache-key')->once();

        $tldManager = $this->app->make(TldManager::class);
        $tldManager->forget();
    }
}
