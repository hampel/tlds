<?php namespace Hampel\Tlds\Console;

use Hampel\Tlds\Exceptions\FetchException;
use Hampel\Tlds\TldManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UpdateTlds extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tld:update';

    /**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Fetch the latest version of the TLD list and refresh the cache';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle(TldManager $tldManager)
	{
        // clear the cache
        $tldManager->forget();

		try
		{
			$tlds = $tldManager->fresh();
			$tldManager->put($tlds);
		}
		catch (FetchException $e)
		{
			$this->reportSkipped($tldManager);
			$this->error($e->getMessage() . ($e->getCode() ? " [" . $e->getCode() . "]" : ""));

			return 1; // failure
		}

		$this->reportSkipped($tldManager);
		$this->info("Added " . count($tlds) . " TLDs to the cache");

		return 0; // success
	}

	/**
	 * Report any lines the source data was rejected for, where someone is looking
	 *
	 * @return void
	 */
	protected function reportSkipped(TldManager $tldManager) : void
	{
		foreach ($tldManager->skipped() as $line)
		{
			$this->warn("Skipped [{$line}] - did not match the TLD format");
		}
	}
}
