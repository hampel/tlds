<?php namespace Hampel\Tlds;

use GuzzleHttp\Client;
use Hampel\Tlds\Console\UpdateTlds;
use Illuminate\Support\ServiceProvider;

class TldServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->package('hampel/tlds', 'tlds', __DIR__);

		$this->registerTlds();
		$this->registerCommands();
	}

	protected function registerTlds()
	{
		$this->app->bindShared(
			'tlds', function ()
			{
				$type = $this->app['config']->get('tlds::source.type');

				return new Tlds(
					$this->app['config'],
					$this->app['cache.store'],
					$this->app['log'],
					$type == 'filesystem' ? $this->getFilesystem($type) : null,
					$type == 'url' ? new Client() : null
				);
			}
		);
	}

	/**
	 * @param $type
	 */
	protected function getFilesystem($type)
	{
		$disk = $this->app['config']->get('tlds::source.disk');

		if ($disk == 'default') $disk = $this->app['config']->get('filesystems.default');

		return $this->app['filesystem']->disk($disk);
	}

	protected function registerCommands()
	{
		$this->app->bindShared('tlds.command.update.tlds', function()
		{
			return new UpdateTlds($this->app['tlds'], $this->app['config'], $this->app['cache.store']);
		});

		$this->commands('tlds.command.update.tlds');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['tlds'];
	}
}