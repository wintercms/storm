<?php

namespace Winter\Storm\Cache;

use Illuminate\Cache\CacheManager as BaseCacheManager;

class CacheManager extends BaseCacheManager
{
    /**
     * Get a cache store instance by name, wrapped in a repository.
     *
     * @param  string|null  $name
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function store($name = null)
    {
        return parent::store($name);
    }
}
