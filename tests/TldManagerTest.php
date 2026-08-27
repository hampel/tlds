<?php namespace Hampel\Tlds\Tests;

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

            public function __construct(protected string $data) {}

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
        return file_get_contents(__DIR__ . '/mock/' . $name);
    }

    public function testGetReturnsCachedDataWithoutFetching()
    {
        $this->cache->put('cache-key', ['foo'], 66);

        $tldManager = $this->manager($this->fixture('tlds-alpha-by-domain.txt'));

        $this->assertEquals(['foo'], $tldManager->get());
    }

    public function testGetPopulatesTheCacheUnderTheConfiguredKey()
    {
        $tldManager = $this->manager($this->fixture('tlds-alpha-by-domain.txt'), 'somewhere-else');

        $tlds = $tldManager->get();

        $this->assertEquals(725, count($tlds));
        $this->assertEquals($tlds, $this->cache->get('somewhere-else'));
    }

    public function testFreshWithNoDataThrowsException()
    {
        $this->expectException(NoDataException::class);
        $this->expectExceptionMessage("No data returned from TLD fetch");

        $this->manager('')->fresh();
    }

    public function testFreshWithBadTldsThrowsException()
    {
        $tldManager = $this->manager($this->fixture('bad-tlds.txt'));

        try
        {
            $tldManager->fresh();
            $this->fail('expected a NoDataException');
        }
        catch (NoDataException $e)
        {
            $this->assertEquals("No data returned from TLD fetch", $e->getMessage());
        }

        // every line was rejected, which is precisely when the caller needs to see them
        $this->assertEquals(
            ['not a valid tld', 'alsonotavalidtld!', 'xn--', 'a', 'com2'],
            $tldManager->skipped()
        );
    }

    public function testFreshWithValidTldsReturnsArray()
    {
        $tldManager = $this->manager($this->fixture('tlds-alpha-by-domain.txt'));

        $tlds = $tldManager->fresh();

        $this->assertIsArray($tlds);
        $this->assertEquals(725, count($tlds));
        $this->assertEmpty($tldManager->skipped());
    }

    public function testProcessWithNoDataReturnsEmptyArray()
    {
        $tlds = $this->manager()->process('');

        $this->assertIsArray($tlds);
        $this->assertEmpty($tlds);
    }

    public function testProcessReturnsFilteredData()
    {
        $tldManager = $this->manager();

        $tlds = $tldManager->process($this->fixture('filter-test.txt'));

        $this->assertIsArray($tlds);
        $this->assertEquals(['academy', 'accountants', 'xn--1qqw23a', 'xn--3bst00m'], $tlds);
        $this->assertEquals(['123', 'test@example.com'], $tldManager->skipped());
    }

    public function testPutStoresUnderTheConfiguredKey()
    {
        $this->manager()->put(['foo']);

        $this->assertEquals(['foo'], $this->cache->get('cache-key'));
    }

    public function testForgetForgetsCacheKey()
    {
        $this->cache->put('cache-key', ['foo'], 66);

        $this->manager()->forget();

        $this->assertNull($this->cache->get('cache-key'));
    }
}
