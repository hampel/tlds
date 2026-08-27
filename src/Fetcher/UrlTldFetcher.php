<?php

namespace Hampel\Tlds\Fetcher;

use Hampel\Tlds\Exceptions\HttpException;
use Illuminate\Http\Client\Factory as Http;

class UrlTldFetcher implements TldFetcher
{
    /** @var Http */
    protected $http;

    /** @var string */
    protected $url;

    /**
     * @param Http $http the application's HTTP client
     * @param string $url where to fetch the TLD list from
     */
    public function __construct(Http $http, string $url)
    {
        $this->http = $http;
        $this->url = $url;
    }

    /**
     * @return string TLD data
     *
     * @throws HttpException when fetch failed
     */
    public function fetchTlds()
    {
        $response = $this->http->get($this->url);

        if ($response->failed()) {
            throw new HttpException("HTTP get failed fetching TLDs from {$this->url}", $response->status());
        }

        return $response->body();
    }
}
