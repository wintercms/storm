<?php namespace Winter\Storm\Halcyon;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\App;
use Winter\Storm\Support\Facades\Config;

class MemoryCacheManager extends CacheManager
{
    public function repository(Store $store, array $config = [])
    {
        return new MemoryRepository($store, $config);
    }

    public static function isEnabled()
    {
        $disabled = Config::get('cache.disableRequestCache', null);
        if ($disabled === null) {
            return !App::runningInConsole() || static::runningInApplicationServer();
        }

        return !$disabled;
    }

    /**
     * Determine whether a persistent application server is handling HTTP requests.
     *
     * runningInConsole() only reports the SAPI, so it cannot tell a console command apart from an
     * application server such as Laravel Octane, which boots from the CLI and then serves HTTP.
     * Without this check the request cache silently stays disabled under exactly the runtime that
     * was adopted for throughput.
     *
     * Octane binds its client contract into the base container when the worker boots, which is the
     * cheapest reliable signal and is absent from ordinary console commands.
     *
     * @return bool
     */
    protected static function runningInApplicationServer()
    {
        $app = App::getFacadeRoot();

        return $app instanceof \Winter\Storm\Foundation\Application
            ? $app->runningInApplicationServer()
            : App::bound('Laravel\Octane\Contracts\Client');
    }

    /**
     * Discard cached records belonging to the previous operation.
     *
     * Only the in-memory request cache is cleared. External cache stores keep their own
     * invalidation rules, so flush() is deliberately not used here.
     *
     * @return void
     */
    public function flushRequestCache()
    {
        foreach ($this->stores as $store) {
            if ($store instanceof MemoryRepository) {
                $store->flushInternalCache();
            }
        }
    }
}
