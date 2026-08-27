<?php  namespace Hampel\Tlds;

use Hampel\Tlds\Exceptions\NoDataException;
use Hampel\Tlds\Fetcher\TldFetcher;
use Illuminate\Contracts\Cache\Repository as Cache;

class TldManager
{
	/** @var TldFetcher */
	protected $fetcher;

	/** @var Cache */
	protected $cache;

	/** @var string */
	protected $key;

	/** @var int */
	protected $expiry;

	/** @var array<int, string> lines rejected by the last process() call */
	protected $skipped = [];

	/**
	 * @param TldFetcher $fetcher source of the raw TLD data
	 * @param Cache $cache the store the application wants this data kept in
	 * @param string $key cache key to store the data under
	 * @param int $expiry how long to keep it, in seconds
	 */
	public function __construct(TldFetcher $fetcher, Cache $cache, string $key, int $expiry)
	{
		$this->fetcher = $fetcher;
		$this->cache = $cache;
		$this->key = $key;
		$this->expiry = $expiry;
	}

    /**
     * Get TLD data from the cache, or fetch a fresh copy if expired
     *
     * @return array<int, string> a list of TLDs
     */
	public function get() : array
	{
		return $this->cache->remember($this->key, $this->expiry, function ()
		{
			return $this->fresh();
		});
	}

    /**
     * Retrieve and process a fresh copy of the TLD data from our source
     *
     * @return array<int, string> array of TLD data
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
     * @return array<int, string>
     */
    public function process(string $data) : array
    {
        $tlds = [];
        $this->skipped = [];

        // tlds are listed one per line - split them and process individually
        foreach (explode("\n", $data) as $tld)
        {
            $tld = trim($tld);
            if (empty($tld)) continue; // skip blank lines
            if (substr($tld, 0, 1) == "#") continue; // skip # comments

            if (!preg_match('/^(?:[a-z]{2,63}|xn--[a-z0-9\-]+)$/i', $tld))
            {
                $this->skipped[] = $tld; // record it for the caller to report
                continue; // skip any invalid lines
            }

            $tlds[] = strtolower($tld);
        }

        return $tlds;
    }

    /**
     * The lines the last process() call rejected
     *
     * @return array<int, string>
     */
    public function skipped() : array
    {
        return $this->skipped;
    }

    /**
     * Store TLD data in the cache
     *
     * @param array<int, string> $data the TLD data to store in the cache
     * @return void
     */
    public function put(array $data) : void
    {
        $this->cache->put($this->key, $data, $this->expiry);
    }

    /**
     * Clear the cache
     *
     * @return void
     */
	public function forget() : void
	{
		$this->cache->forget($this->key);
	}
}
