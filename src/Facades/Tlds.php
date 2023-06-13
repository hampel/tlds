<?php namespace Hampel\Tlds\Facades;

use Hampel\Tlds\TldManager;
use Illuminate\Support\Facades\Facade;

class Tlds extends Facade {

    protected static function getFacadeAccessor() { return TldManager::class; }

}
