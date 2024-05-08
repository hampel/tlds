<?php namespace Hampel\Tlds\Fetcher;

use Hampel\Tlds\Exceptions\FetchException;

interface TldFetcher
{
    /**
     * @return string TLD data
     *
     * @throws FetchException
     */
	public function fetchTlds();
}
