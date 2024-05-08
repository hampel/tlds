<?php namespace Hampel\Tlds\Tests\Fetcher;

use Hampel\Tlds\Exceptions\HttpException;
use Hampel\Tlds\Fetcher\UrlTldFetcher;
use Hampel\Tlds\Tests\UnitTestCase;
use Illuminate\Support\Facades\Http;

class UrlTldFetcherTest extends UnitTestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = $app->get('config');

        $config->set('tlds.source', 'url');
        $config->set('tlds.url', 'http://www.example.com/tlds.txt');
    }

    public function testfetchTlds404Error()
    {
        Http::fake([
            '*' => Http::response('Error', 404),
        ]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("HTTP get failed fetching TLDs from http://www.example.com/tlds.txt");

        $fetcher = $this->app->make(UrlTldFetcher::class);
        $fetcher->fetchTlds();
    }

    public function testfetchTldsDataReturned()
    {
        Http::fake([
            '*' => Http::response('foo', 200),
        ]);

        $fetcher = $this->app->make(UrlTldFetcher::class);
        $this->assertEquals('foo', $fetcher->fetchTlds());
    }
}
