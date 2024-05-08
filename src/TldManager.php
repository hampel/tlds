<?php  namespace Hampel\Tlds;

use Hampel\Tlds\Exceptions\NoDataException;
use Hampel\Tlds\Fetcher\TldFetcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class TldManager
{
	/** @var TldFetcher */
	protected $fetcher;

	public function __construct(TldFetcher $fetcher)
	{
		$this->fetcher = $fetcher;
	}

    /**
     * Get TLD data from the cache, or fetch a fresh copy if expired
     *
     * @return array a list of TLDs
     */
	public function get() : array
	{
		return Cache::remember(
            Config::get('tlds.cache.key'),
            Config::get('tlds.cache.expiry'),
            function()
            {
                return $this->fresh();
            });
	}

    /**
     * Retrieve and process a fresh copy of the TLD data from our source
     *
     * @return array array of TLD data
     *
     * @throws NoDataException when no valid data was found
     */
    public function fresh() : array
    {
        $tlds = $this->process($this->fetch());

        if (empty($tlds))
        {
            throw new NoDataException("No data returned from TLD fetch");
        }

        return $tlds;
    }

    /**
     * Fetch the TLDs from our source
     *
     * @return string a newline separated list of TLDs
     */
	public function fetch() : string
	{
        return $this->fetcher->fetchTlds();
    }

    /**
     * @param string $data a newline separated list of TLDs
     * @return array
     */
    public function process(string $data) : array
    {
        $tlds = [];

        // tlds are listed one per line - split them and process individually
        foreach (explode("\n", $data) as $tld)
        {
            $tld = trim($tld);
            if (empty($tld)) continue; // skip blank lines
            if (substr($tld, 0, 1) == "#") continue; // skip # comments

            if (!preg_match('/^(?:[a-z]{2,63}|xn--[a-z0-9\-]+)$/i', $tld))
            {
                Log::warning("[TLDs] Skipped [{$tld}] - did not match regex validator");
                continue; // skip any invalid lines
            }

            $tlds[] = strtolower($tld);
        }

        return $tlds;
    }

    /**
     * Store TLD data in the cache
     *
     * @param array $data the TLD data to store in the cache
     * @return void
     */
    public function put(array $data) : void
    {
        Cache::put(
            Config::get('tlds.cache.key'),
            $data,
            Config::get('tlds.cache.expiry')
        );
    }

    /**
     * Clear the cache
     *
     * @return void
     */
	public function forget() : void
	{
		Cache::forget(Config::get('tlds.cache.key'));
	}
}
