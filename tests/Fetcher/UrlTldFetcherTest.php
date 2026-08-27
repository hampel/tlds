<?php namespace Hampel\Tlds\Tests\Fetcher;

use Hampel\Tlds\Exceptions\HttpException;
use Hampel\Tlds\Fetcher\UrlTldFetcher;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\TestCase;

/**
 * The fetcher is handed a client and a URL, so this needs no application either.
 */
class UrlTldFetcherTest extends TestCase
{
    protected const URL = 'http://www.example.com/tlds.txt';

    public function testFetchTlds404Error(): void
    {
        $http = new Factory();
        $http->fake(['*' => $http->response('Error', 404)]);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage("HTTP get failed fetching TLDs from " . self::URL);
        $this->expectExceptionCode(404);

        (new UrlTldFetcher($http, self::URL))->fetchTlds();
    }

    public function testFetchTldsDataReturned(): void
    {
        $http = new Factory();
        $http->fake(['*' => $http->response('foo', 200)]);

        $this->assertSame('foo', (new UrlTldFetcher($http, self::URL))->fetchTlds());
    }

    public function testFetchTldsRequestsTheConfiguredUrl(): void
    {
        $http = new Factory();
        $http->fake(['*' => $http->response('foo', 200)]);

        (new UrlTldFetcher($http, self::URL))->fetchTlds();

        $http->assertSent(fn (Request $request) => $request->url() === self::URL);
    }
}
