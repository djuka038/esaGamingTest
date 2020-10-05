<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;

class CacheService {

    /**
     *
     * CREATE
     *
     */

    /**
     * Caching data
     *
     * @param  string $key      Key
     * @param  string $value    Value
     * @return void
     */
    public static function cacheData(
        $key,
        $value
    ) {
        Cache::put($key, $value);
    }

    /**
     *
     * READ
     *
     */

    /**
     * Geting cached data by key
     *
     * @param  string $key
     * @return mixed
     */
    public static function getCachedDataByKey($key) {
        return Cache::get($key);
    }

    /**
     * Checking has key exists
     *
     * @param  string  $key
     * @return boolean
     */
    public static function hasKeyExists($key) {
        return Cache::has($key);
    }

    /**
     *
     * UPDATE
     *
     */

    /**
     *
     * DELETE
     *
     */

    /**
     * Removing cached key
     *
     * @param  string $key
     * @return void
     */
    public static function removeCachedDataByKey($key) {
        Cache::forget($key);
    }

    /**
     * Flushing all cache
     *
     * @return void
     */
    public static function flushCache() {
        Cache::flush();
    }
}
