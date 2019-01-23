<?php namespace Hampel\Tlds;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Hampel\Tlds\Fetcher\TldFetcher;
use Hampel\Tlds\Console\UpdateTlds;
use Hampel\Tlds\Fetcher\UrlTldFetcher;
use Illuminate\Support\ServiceProvider;
use Hampel\Tlds\Fetcher\FilesystemTldFetcher;
use Hampel\Tlds\Validation\ValidatorExtensions;

class TldServiceProvider extends ServiceProvider
{

	protected $rules = [
		'domain', 'domain_in', 'tld', 'tld_in'
	];

	protected $replacers = [
		'domain_in', 'tld_in'
	];

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register(){}

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->defineConfiguration();
		$this->defineTranslations();

		$this->registerTlds();
		$this->addNewRules();
		$this->addNewReplacers();

		$this->registerCommands();
	}

	protected function defineConfiguration()
	{
		$this->publishes([
			__DIR__ . '/config/tlds.php' => config_path('tlds.php'),
		], 'config');

		$this->mergeConfigFrom(
			__DIR__ . '/config/tlds.php', 'tlds'
		);
	}

	protected function defineTranslations()
	{
		$this->loadTranslationsFrom(__DIR__ . '/lang', 'tlds');
	}

	protected function registerTlds()
	{
		$type = $this->app['config']->get('tlds.source');

		$this->app->bind(
			TldFetcher::class,
			$type == 'filesystem' ? FilesystemTldFetcher::class : UrlTldFetcher::class
		);

		$this->app->bind(ClientInterface::class, function ()
		{
			return new Client();
		});
	}

	protected function addNewRules()
	{
		foreach ($this->rules as $rule)
		{
			$this->extendValidator($rule);
		}
	}

	protected function extendValidator($rule)
	{
		$method = 'validate' . studly_case($rule);
		$translation = $this->app['translator']->get('tlds::validation');

		$this->app['validator']->extend($rule, ValidatorExtensions::class . "@{$method}", $translation[$rule]);
	}

	protected function addNewReplacers()
	{
		foreach ($this->replacers as $rule)
		{
			$this->addReplacer($rule);
		}
	}

	protected function addReplacer($rule)
	{
		$method = 'replace' . studly_case($rule);

		$this->app['validator']->replacer($rule, ValidatorExtensions::class . "@{$method}");
	}

	protected function registerCommands()
	{
		if ($this->app->runningInConsole())
		{
			$this->commands([
				UpdateTlds::class,
			]);
		}
	}
}
