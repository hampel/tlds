<?php

namespace Hampel\Tlds\Tests;

use Hampel\Tlds\Exceptions\NoDataException;
use Hampel\Tlds\Fetcher\TldFetcher;
use Hampel\Tlds\TldManager;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;

/**
 * Everything here is constructed directly - the manager takes its collaborators
 * rather than reaching for facades, so none of it needs a booted application.
 */
class TldManagerTest extends TestCase
{
    protected Repository $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new Repository(new ArrayStore());
    }

    protected function manager(string $data = '', string $key = 'cache-key', int $expiry = 66): TldManager
    {
        $fetcher = new class ($data) implements TldFetcher {
            public int $calls = 0;

            public function __construct(protected string $data)
            {
            }

            public function fetchTlds()
            {
                $this->calls++;

                return $this->data;
            }
        };

        return new TldManager($fetcher, $this->cache, $key, $expiry);
    }

    protected function fixture(string $name): string
    {
        $data = file_get_contents(__DIR__ . '/mock/' . $name);
        $this->assertIsString($data, "could not read fixture {$name}");

        return $data;
    }

    public function testGetReturnsCachedDataWithoutFetching(): void
    {
        $this->cache->put('cache-key', ['foo'], 66);

        $tldManager = $this->manager($this->fixture('tlds-alpha-by-domain.txt'));

        $this->assertSame(['foo'], $tldManager->get());
    }

    public function testGetPopulatesTheCacheUnderTheConfiguredKey(): void
    {
        $tldManager = $this->manager($this->fixture('tlds-alpha-by-domain.txt'), 'somewhere-else');

        $tlds = $tldManager->get();

        $this->assertSame(725, count($tlds));
        $this->assertSame($tlds, $this->cache->get('somewhere-else'));
    }

    public function testFreshWithNoDataThrowsException(): void
    {
        $this->expectException(NoDataException::class);
        $this->expectExceptionMessage("No data returned from TLD fetch");

        $this->manager('')->fresh();
    }

    public function testFreshWithBadTldsThrowsException(): void
    {
        $tldManager = $this->manager($this->fixture('bad-tlds.txt'));

        try {
            $tldManager->fresh();
            $this->fail('expected a NoDataException');
        } catch (NoDataException $e) {
            $this->assertSame("No data returned from TLD fetch", $e->getMessage());
        }

        // every line was rejected, which is precisely when the caller needs to see them
        $this->assertSame(
            ['not a valid tld', 'alsonotavalidtld!', 'xn--', 'a', 'com2'],
            $tldManager->skipped()
        );
    }

    public function testFreshWithValidTldsReturnsArray(): void
    {
        $tldManager = $this->manager($this->fixture('tlds-alpha-by-domain.txt'));

        $tlds = $tldManager->fresh();

        $this->assertSame(725, count($tlds));
        $this->assertEmpty($tldManager->skipped());
    }

    public function testProcessWithNoDataReturnsEmptyArray(): void
    {
        $tlds = $this->manager()->process('');

        $this->assertEmpty($tlds);
    }

    public function testProcessReturnsFilteredData(): void
    {
        $tldManager = $this->manager();

        $tlds = $tldManager->process($this->fixture('filter-test.txt'));

        $this->assertSame(['academy', 'accountants', 'xn--1qqw23a', 'xn--3bst00m'], $tlds);
        $this->assertSame(['123', 'test@example.com'], $tldManager->skipped());
    }

    public function testPutStoresUnderTheConfiguredKey(): void
    {
        $this->manager()->put(['foo']);

        $this->assertSame(['foo'], $this->cache->get('cache-key'));
    }

    public function testForgetForgetsCacheKey(): void
    {
        $this->cache->put('cache-key', ['foo'], 66);

        $this->manager()->forget();

        $this->assertNull($this->cache->get('cache-key'));
    }
}
