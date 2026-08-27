<?php

namespace Hampel\Tlds\Facades;

use Hampel\Tlds\TldManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<int, string> get() TLD list from the cache, refreshed if expired
 * @method static array<int, string> fresh() a freshly fetched TLD list, bypassing the cache
 * @method static string fetch() the raw, unprocessed source data
 * @method static array<int, string> process(string $data) parse raw source data into a TLD list
 * @method static array<int, string> skipped() the lines the last process() call rejected
 * @method static void put(array<int, string> $data) store a TLD list in the cache
 * @method static void forget() clear the cached TLD list
 *
 * @see TldManager
 */
class Tlds extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TldManager::class;
    }

}
