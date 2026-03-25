<?php

namespace Illuminate\Cache;

use Illuminate\Cache\Events\CacheFallbackStoreFailed;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Events\Dispatcher;
use RuntimeException;
use Throwable;

class FallbackStore extends TaggableStore implements LockProvider
{
    /**
     * The caches which failed on the last write action.
     *
     * @var list<string>
     */
    protected array $failingCaches = [];

    /**
     * Create a new fallback store.
     *
     * @param  array<int, string>  $stores
     */
    public function __construct(
        protected CacheManager $cache,
        protected Dispatcher $events,
        protected array $stores
    ) {
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->attemptOnAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @return array
     */
    public function many(array $keys)
    {
        return $this->attemptOnAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        return $this->writeToAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     *
     * @param  int  $seconds
     * @return bool
     */
    public function putMany(array $values, $seconds)
    {
        return $this->writeToAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function add($key, $value, $seconds)
    {
        return $this->writeToAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|false
     */
    public function increment($key, $value = 1)
    {
        return $this->writeToAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|false
     */
    public function decrement($key, $value = 1)
    {
        return $this->writeToAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->writeToAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Get a lock instance.
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function lock($name, $seconds = 0, $owner = null)
    {
        return $this->attemptOnAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Restore a lock instance using the owner identifier.
     *
     * @param  string  $name
     * @param  string  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function restoreLock($name, $owner)
    {
        return $this->attemptOnAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Adjust the expiration time of a cached item.
     *
     * @param  string  $key
     * @param  int  $seconds
     * @return bool
     */
    public function touch($key, $seconds)
    {
        return $this->writeToAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        return $this->writeToAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        return $this->writeToAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Remove all expired tag set entries.
     *
     * @return void
     */
    public function flushStaleTags()
    {
        foreach ($this->stores as $store) {
            if ($this->store($store)->getStore() instanceof RedisStore) {
                $this->store($store)->flushStaleTags();
            }
        }
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->attemptOnAllStores(__FUNCTION__, func_get_args());
    }

    /**
     * Attempt the given method on each store, returning the first successful result.
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    protected function attemptOnAllStores(string $method, array $arguments)
    {
        $lastException = null;

        foreach ($this->stores as $store) {
            try {
                return $this->store($store)->{$method}(...$arguments);
            } catch (Throwable $e) {
                $lastException = $e;
            }
        }

        throw $lastException ?? new RuntimeException('All fallback cache stores failed.');
    }

    /**
     * Write the given method to all stores, returning the primary store's result.
     * Failures on secondary stores dispatch an event but do not interrupt the operation.
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    protected function writeToAllStores(string $method, array $arguments)
    {
        [$result, $failedCaches, $primaryWritten] = [null, [], false];

        try {
            foreach ($this->stores as $store) {
                try {
                    $value = $this->store($store)->{$method}(...$arguments);

                    if (! $primaryWritten) {
                        $result = $value;
                        $primaryWritten = true;
                    }
                } catch (Throwable $e) {
                    if (! $primaryWritten) {
                        throw $e;
                    }

                    $failedCaches[] = $store;

                    if (! in_array($store, $this->failingCaches)) {
                        $this->events->dispatch(new CacheFallbackStoreFailed($store, $e));
                    }
                }
            }
        } finally {
            $this->failingCaches = $failedCaches;
        }

        return $result;
    }

    /**
     * Get the cache store for the given store name.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function store(string $store)
    {
        return $this->cache->store($store);
    }
}
