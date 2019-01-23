<?php  namespace Hampel\Tlds;

use Psr\Log\LoggerInterface;
use Hampel\Tlds\Fetcher\TldFetcher;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;

class Tlds 
{
	/** @var Config */
	protected $config;

	/** @var Cache */
	protected $cache;

	/** @var LoggerInterface */
	protected $logger;

	/** @var TldFetcher */
	protected $fetcher;

	public function __construct(Config $config, Cache $cache, LoggerInterface $logger, TldFetcher $fetcher)
	{
		$this->config = $config;
		$this->cache = $cache;
		$this->logger = $logger;
		$this->fetcher = $fetcher;
	}

	public function get()
	{
		$cache_key = $this->config->get('tlds.cache.key');
		$cache_expiry = $this->config->get('tlds.cache.expiry');

		$tlds = $this->cache->remember($cache_key, $cache_expiry, function()
		{
			return $this->fresh();
		});

		return $tlds;
	}

	public function fresh()
	{
		$tlds = [];

		$tld_array = explode("\n", $this->fetchTlds());
		foreach ($tld_array as $tld)
		{
			$tld = trim($tld);
			if (empty($tld)) continue; // skip blank lines
			if (substr($tld, 0, 1) == "#") continue; // skip # comments

			if (!preg_match('/^(?:[a-z]{2,63}|xn--[a-z0-9\-]+)$/i', $tld))
			{
				$this->logger->warning("Skipped TLD [{$tld}] - did not match regex validator");
				continue; // skip any invalid lines
			}

			$tlds[] = strtolower($tld);
		}

		$this->logger->info("Added " . count($tlds) . " TLDs to cache");

		return $tlds;
	}

	/**
	 * @return string
	 */
	protected function fetchTlds()
	{
		return $this->fetcher->fetchTlds();
	}

	public function forget()
	{
		$cache_key = $this->config->get('tlds.cache.key');

		$this->cache->forget($cache_key);
	}
}
