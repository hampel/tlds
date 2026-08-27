<?php namespace Hampel\Tlds\Tests;

use Hampel\Tlds\Fetcher\TldFetcher;
use Hampel\Tlds\Fetcher\UrlTldFetcher;
use Hampel\Tlds\TldManager;
use Hampel\Tlds\TldServiceProvider;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;

/**
 * The provider's own registration, which nothing else in the suite reaches.
 *
 * Every other test resolves out of an application Testbench had already registered
 * the provider into, so they exercise what registration installs and never the
 * statements that install it. Testbench also boots that application inside
 * parent::setUp(), before UnitTestCase can put PHPUnit's strict error handler back -
 * so phpunit.xml's failOnDeprecation cannot see the provider either. Probed: a
 * deprecation in boot() left the suite green at exit 0.
 *
 * Illuminate\Foundation\Application runs no bootstrappers when constructed directly,
 * so HandleExceptions never fires and PHPUnit keeps its handler throughout.
 *
 * It earns its place twice over. The application built here carries a config
 * repository and a cache factory and nothing else, so it is also the assertion that
 * this package's wiring resolves without the rest of the framework present.
 */
class ProviderRegistrationTest extends TestCase
{
    protected function application(): Application
    {
        $app = new Application(dirname(__DIR__));

        $app->instance('config', new ConfigRepository());

        $app->bind(CacheFactory::class, function (): CacheFactory {
            return new class () implements CacheFactory {
                public function store($name = null): Cache
                {
                    return new CacheRepository(new ArrayStore());
                }
            };
        });

        return $app;
    }

    public function testRegisterMergesThePackageConfiguration(): void
    {
        $app = $this->application();

        (new TldServiceProvider($app))->register();

        $this->assertSame(
            'http://data.iana.org/TLD/tlds-alpha-by-domain.txt',
            $app->make('config')->get('tlds.url')
        );
        $this->assertSame(86400, $app->make('config')->get('tlds.cache.expiry'));
    }

    public function testBootBindsTheFetcher(): void
    {
        $app = $this->application();

        $provider = new TldServiceProvider($app);
        $provider->register();
        $provider->boot();

        $this->assertTrue($app->bound(TldFetcher::class));
        $this->assertInstanceOf(UrlTldFetcher::class, $app->make(TldFetcher::class));
    }

    public function testBootBindsAManagerThatResolvesWithoutTheRestOfTheFramework(): void
    {
        $app = $this->application();

        $provider = new TldServiceProvider($app);
        $provider->register();
        $provider->boot();

        $this->assertTrue($app->bound(TldManager::class));
        $this->assertInstanceOf(TldManager::class, $app->make(TldManager::class));
    }

    public function testTheManagerIsSharedRatherThanRebuiltPerResolution(): void
    {
        $app = $this->application();

        $provider = new TldServiceProvider($app);
        $provider->register();
        $provider->boot();

        $this->assertSame($app->make(TldManager::class), $app->make(TldManager::class));
    }
}
