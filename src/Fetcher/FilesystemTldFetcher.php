<?php namespace Hampel\Tlds\Fetcher;

use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Hampel\Tlds\Exceptions\FilesystemException;
use Hampel\Tlds\Exceptions\BadResponseException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class FilesystemTldFetcher implements TldFetcher
{
	/** @var Filesystem */
	protected $filesystem;

	/** @var Repository */
	protected $config;

	/** @var LoggerInterface */
	protected $logger;

	public function __construct(Filesystem $filesystem, Repository $config, LoggerInterface $logger)
	{
		$this->filesystem = $filesystem;
		$this->config = $config;
		$this->logger = $logger;
	}

	public function fetchTlds()
	{
		$path = $this->config->get('tlds.path');

		$this->logger->info("Fetching updated TLDs from Filesystem: {$path}");

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
}
