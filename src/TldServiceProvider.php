<?php namespace Hampel\Tlds;

use Hampel\Tlds\Fetcher\TldFetcher;
use Hampel\Tlds\Console\UpdateTlds;
use Hampel\Tlds\Fetcher\UrlTldFetcher;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
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
		$this->app->bind(TldFetcher::class, function ($app)
		{
			return new UrlTldFetcher(
				$app->make(HttpFactory::class),
				$app['config']->get('tlds.url')
			);
		});

		// this provider is the only place that reads configuration: everything
		// downstream is handed what it needs, including the store the application
		// wants this data kept in
		$this->app->singleton(TldManager::class, function ($app)
		{
			$config = $app['config'];

			return new TldManager(
				$app->make(TldFetcher::class),
				$app->make(CacheFactory::class)->store($config->get('tlds.cache.store')),
				$config->get('tlds.cache.key'),
				(int) $config->get('tlds.cache.expiry')
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
