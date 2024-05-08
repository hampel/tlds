<?php namespace Hampel\Tlds\Fetcher;

use Hampel\Tlds\Exceptions\HttpException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class UrlTldFetcher implements TldFetcher
{
    /**
     * @return string TLD data
     *
     * @throws HttpException when fetch failed
     */
	public function fetchTlds()
	{
        $url = Config::get('tlds.url');

		$response = Http::get($url);
        if ($response->failed())
        {
            throw new HttpException("HTTP get failed fetching TLDs from {$url}", $response->status());
        }

		return $response->body();
	}
}
