<?php namespace Hampel\Tlds\Console;

use Hampel\Tlds\Tlds;

use Illuminate\Console\Command;
use Illuminate\Config\Repository as Config;
use \Illuminate\Contracts\Cache\Repository as Cache;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UpdateTlds extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'tld:update';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Fetch the latest version of the TLD list and refresh the cache';

	/**
	 * @var \Hampel\Tlds\Tlds
	 */
	private $tlds;

	/**
	 * @var \Illuminate\Config\Repository
	 */
	private $config;

	/**
	 * @var \Illuminate\Contracts\Cache\Repository
	 */
	private $cache;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct(Tlds $tlds, Config $config, Cache $cache)
	{
		parent::__construct();
		$this->tlds = $tlds;
		$this->config = $config;
		$this->cache = $cache;
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		try
		{
			$tlds = $this->tlds->fresh();

			$expiry = $this->config->get('tlds::cache.expiry');
			$key = $this->config->get('tlds::cache.key');

			$this->cache->put($key, $tlds, $expiry);

			$this->info("Added " . count($tlds) . " TLDs to the TLD Cache");
		}
		catch (\Exception $e)
		{
			$this->error($e->getMessage() . ($e->getCode() ? " [" . $e->getCode() . "]" : ""));
		}
	}
}
