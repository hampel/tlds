<?php namespace Hampel\Tlds\Fetcher;

use Psr\Log\LoggerInterface;
use GuzzleHttp\ClientInterface;
use Hampel\Tlds\Exceptions\HttpException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Config\Repository;
use Hampel\Tlds\Exceptions\BadResponseException;

class UrlTldFetcher implements TldFetcher
{
	/** @var ClientInterface */
	protected $guzzle;

	/** @var Repository */
	protected $config;

	/** @var LoggerInterface */
	protected $logger;

	public function __construct(ClientInterface $guzzle, Repository $config, LoggerInterface $logger)
	{
		$this->guzzle = $guzzle;
		$this->config = $config;
		$this->logger = $logger;
	}

	public function fetchTlds()
	{
		$url = $this->config->get('tlds.url');

		$this->logger->info("Fetching updated TLDs from URL: {$url}");

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
}
