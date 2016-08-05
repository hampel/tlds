<?php  namespace Hampel\Tlds;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

use Illuminate\Contracts\Logging\Log;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

use Hampel\Tlds\Exceptions\HttpException;
use Hampel\Tlds\Exceptions\FilesystemException;
use Hampel\Tlds\Exceptions\BadResponseException;
use Hampel\Tlds\Exceptions\ServiceProviderException;

class Tlds 
{
	/**
	 * @var \Illuminate\Contracts\Config\Repository
	 */
	private $config;

	/**
	 * @var \Illuminate\Contracts\Cache\Repository
	 */
	private $cache;

	/**
	 * @var \Illuminate\Contracts\Logging\Log
	 */
	private $log;

	/**
	 * @var \Illuminate\Contracts\Filesystem\Filesystem
	 */
	private $filesystem;

	/**
	 * @var \GuzzleHttp\ClientInterface
	 */
	private $guzzle;

	public function __construct(Config $config, Cache $cache, Log $log, Filesystem $filesystem = null, ClientInterface $guzzle = null)
	{

		$this->config = $config;
		$this->cache = $cache;
		$this->log = $log;
		$this->filesystem = $filesystem;
		$this->guzzle = $guzzle;
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
				$this->log->warning("Skipped TLD [{$tld}] - did not match regex validator");
				continue; // skip any invalid lines
			}

			$tlds[] = strtolower($tld);
		}

		$this->log->info("Added " . count($tlds) . " TLDs to cache");

		return $tlds;
	}

	/**
	 * @return string
	 */
	protected function fetchTlds()
	{
		$type = $this->config->get('tlds.source.type');

		if ($type == 'url')
		{
			return $this->fetchTldsFromUrl();
		}
		else
		{
			return $this->fetchTldsFromFilesystem();
		}
	}

	protected function fetchTldsFromUrl()
	{
		$url = $this->config->get('tlds.source.url');

		$this->log->info("Fetching updated TLDs from URL: {$url}");

		if (!isset($this->guzzle)) throw new ServiceProviderException("Guzzle client not initialised");

		try
		{
			$response = $this->guzzle->request('GET', $url);
		}
		catch (RequestException $e)
		{
			throw new HttpException($e->getMessage(), $e->getCode(), $e);
		}

		$data = strval($response->getBody());

		if (empty($data)) throw new BadResponseException("No data returned when fetching TLDs from URL {$url}");

		return $data;
	}

	protected function fetchTldsFromFilesystem()
	{
		$path = $this->config->get('tlds.source.path');

		$this->log->info("Fetching updated TLDs from Filesystem: {$path}");

		if (!isset($this->filesystem)) throw new ServiceProviderException("Filesystem not initialised");

		try
		{
			$data = $this->filesystem->get($path);
		}
		catch (FileNotFoundException $e)
		{
			throw new FilesystemException($e->getMessage(), $e->getCode(), $e);
		}

		if (empty($data)) throw new BadResponseException("No data returned when fetching TLDs from Filesystem {$path}");

		return $data;
	}

	public function forget()
	{
		$cache_key = $this->config->get('tlds.cache.key');

		$this->cache->forget($cache_key);
	}
}
