<?php namespace Hampel\Tlds;

use GuzzleHttp\Client;
use Hampel\Validate\Validator;
use Hampel\Tlds\Console\UpdateTlds;
use Illuminate\Support\ServiceProvider;
use Hampel\Tlds\Validation\ValidatorExtensions;

class TldServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

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
	public function register()
	{
		$this->registerValidatorLibrary();
		$this->registerCommands();
	}

	protected function registerValidatorLibrary()
	{
		$this->app->singleton('tlds.validator', function()
		{
			return new Validator();
		});
	}

	protected function registerCommands()
	{
		$this->app->singleton('tlds.command.update.tlds', function()
		{
			return new UpdateTlds($this->app['tlds'], $this->app['config'], $this->app['cache.store']);
		});

		$this->commands('tlds.command.update.tlds');
	}

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
		$this->registerValidatorExtension();
		$this->addNewRules();
		$this->addNewReplacers();
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
		$this->app->singleton('tlds', function ()
		{
			$type = $this->app['config']->get('tlds.source.type');

			return new Tlds(
				$this->app['config'],
				$this->app['cache.store'],
				$this->app['log'],
				$type == 'filesystem' ? $this->getFilesystem($type) : null,
				$type == 'url' ? new Client() : null
			);
		});
	}

	/**
	 * @param $type
	 */
	protected function getFilesystem($type)
	{
		$disk = $this->app['config']->get('tlds.source.disk');

		if ($disk == 'default') $disk = $this->app['config']->get('filesystems.default');

		return $this->app['filesystem']->disk($disk);
	}

	protected function registerValidatorExtension()
	{
		$this->app->bind(
			'Hampel\Tlds\Validation\ValidatorExtensions', function ()
			{
				return new ValidatorExtensions($this->app['tlds.validator'], $this->app['tlds']);
			}
		);
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

		$this->app['validator']->extend($rule, 'Hampel\Tlds\Validation\ValidatorExtensions@' . $method, $translation[$rule]);
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

		$this->app['validator']->replacer($rule, 'Hampel\Tlds\Validation\ValidatorExtensions@' . $method);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['tlds', 'tlds.validator', 'tlds.command.update.tlds'];
	}
}
