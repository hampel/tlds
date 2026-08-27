<?php namespace Hampel\Tlds;

use Hampel\Tlds\Fetcher\TldFetcher;
use Hampel\Tlds\Console\UpdateTlds;
use Hampel\Tlds\Fetcher\UrlTldFetcher;
use Illuminate\Support\ServiceProvider;
use Hampel\Tlds\Fetcher\FilesystemTldFetcher;

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
