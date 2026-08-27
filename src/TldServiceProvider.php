<?php namespace Hampel\Tlds;

use Hampel\Tlds\Fetcher\TldFetcher;
use Hampel\Tlds\Console\UpdateTlds;
use Hampel\Tlds\Fetcher\UrlTldFetcher;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

class TldServiceProvider extends ServiceProvider
{
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
        $this->registerCommands();
    }

    /**
     * @return void
     */
	protected function defineConfiguration(): void
	{
		$this->publishes([
            __DIR__ . '/../config/tlds.php' => $this->app->configPath('tlds.php'),
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
		// bound rather than config-dispatched, so an application wanting a different
		// source binds its own TldFetcher implementation and this one gives way
		$this->app->bind(TldFetcher::class, function (Application $app)
		{
			/** @var string $url */
			$url = $app->make(Config::class)->get('tlds.url');

			return new UrlTldFetcher($app->make(HttpFactory::class), $url);
		});

		// this provider is the only place that reads configuration: everything
		// downstream is handed what it needs, including the store the application
		// wants this data kept in
		$this->app->singleton(TldManager::class, function (Application $app)
		{
			$config = $app->make(Config::class);

			/** @var string|null $store */
			$store = $config->get('tlds.cache.store');
			/** @var string $key */
			$key = $config->get('tlds.cache.key');
			/** @var int|string $expiry */
			$expiry = $config->get('tlds.cache.expiry');

			return new TldManager(
				$app->make(TldFetcher::class),
				$app->make(CacheFactory::class)->store($store),
				$key,
				(int) $expiry
			);
		});
	}

    /**
     * @return void
     */
    protected function registerCommands(): void
	{
		if ($this->app->runningInConsole())
		{
			$this->commands([
				UpdateTlds::class,
			]);
		}
	}
}
