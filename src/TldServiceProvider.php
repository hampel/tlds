<?php namespace Hampel\Tlds;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Hampel\Tlds\Fetcher\TldFetcher;
use Hampel\Tlds\Console\UpdateTlds;
use Hampel\Tlds\Fetcher\UrlTldFetcher;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
	public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/tlds.php', 'tlds'
        );
    }

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		$this->defineConfiguration();
		$this->defineTranslations();

		$this->registerTlds();
		$this->addNewRules();
		$this->addNewReplacers();

        $this->registerCommands();
    }

    /**
     * @return void
     */
	protected function defineConfiguration(): void
	{
		$this->publishes([
            __DIR__ . '/../config/tlds.php' => config_path('tlds.php'),
		], 'config');
	}

    /**
     * @return void
     */
	protected function defineTranslations(): void
	{
		$this->loadTranslationsFrom(__DIR__ . '/../lang', 'tlds');

        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/tlds'),
        ], 'lang');
	}

    /**
     * @return void
     */
	protected function registerTlds(): void
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

    /**
     * @return void
     */
	protected function addNewRules(): void
	{
		foreach ($this->rules as $rule)
		{
			$this->extendValidator($rule);
		}
	}

    /**
     * @return void
     */
	protected function extendValidator($rule): void
	{
		$method = 'validate' . Str::studly($rule);
		$translation = $this->app['translator']->get('tlds::validation');

		$this->app['validator']->extend($rule, ValidatorExtensions::class . "@{$method}", $translation[$rule]);
	}

    /**
     * @return void
     */
	protected function addNewReplacers(): void
	{
		foreach ($this->replacers as $rule)
		{
			$this->addReplacer($rule);
		}
	}

    /**
     * @return void
     */
	protected function addReplacer($rule): void
	{
		$method = 'replace' . Str::studly($rule);

		$this->app['validator']->replacer($rule, ValidatorExtensions::class . "@{$method}");
	}

    /**
     * @return void
     */
    protected function registerCommands(): void
	{
		if ($this->app->runningInConsole())
		{
            $this->addAboutOutput();

			$this->commands([
				UpdateTlds::class,
			]);
		}
	}

    /**
     * @return void
     */
    protected function addAboutOutput(): void
    {
        // About command was added in Laravel 9.21.0, so only invoke it if we're running a later version
        if (version_compare($this->app->version(), '9.21.0', '>='))
        {
            AboutCommand::add('TLDs', fn() => ['Source' => $this->app['config']->get('tlds.source')]);
        }
    }
}
