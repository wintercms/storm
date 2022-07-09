<?php namespace Winter\Storm\Halcyon;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\App;
use Winter\Storm\Support\Facades\Config;

class MemoryCacheManager extends CacheManager
{
    public function repository(Store $store)
    {
        return new MemoryRepository($store);
    }

    public static function isEnabled()
    {
        $disabled = Config::get('cache.disableRequestCache', null);
        if ($disabled === null) {
            return !App::runningInConsole();
        }

        return !$disabled;
    }
}
