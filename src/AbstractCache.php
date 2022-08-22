<?php

declare(strict_types=1);

namespace WPGraphQL\Extensions\Cache;

abstract class AbstractCache
{
    /**
     * @type Backend\AbstractBackend
     */
    protected $backend = null;

    /**
     * Expire cached value after given seconds
     */
    protected $expire = 60 * 60;

    /**
     * Restored value from the cache backend
     *
     * @type CachedValue
     */
    protected $cached_value = null;

    /**
     * The cache key
     */
    protected $key = null;

    function __construct()
    {
        $this->backend = $config['backend'] ?? null;
        if (!empty($config['expire'])) {
            $this->expire = intval($config['expire']);
        }
    }

    /**
     * Get the raw cached out of the CachedValue container
     */
    function get_cached_data()
    {
        if (!$this->has_hit()) {
            throw new \Error(
                'No cached value available. Check first with "FieldCache#has_hit()"'
            );
        }

        return $this->cached_value->get_data();
    }

    /**
     * Get the cache key
     */
    function get_cache_key(): string
    {
        if (null === $this->key) {
            throw new \Error(
                'Cache key not generated yet.'
            );
        }

        return $this->key;
    }

    /**
     * Get the cache group
     */
    function get_cache_group(): string
    {
        if (null === $this->cache_group) {
            throw new \Error(
                'Cache group not generated yet.'
            );
        }

        return $this->cache_group;
    }

    /**
     * Read data from the cache backend but discard immediately it if has been expired
     */
    function read_cache()
    {
        $this->cached_value = $this->backend->get(
            $this->get_cache_key(),
            $this->get_cache_group()
        );
    }

    /**
     * Delete the current key from the cache
     */
    function delete()
    {
        $this->cached_value = null;
        $this->backend->delete($this->get_cache_key());
    }

    /**
     * Get the backend instance
     */
    function get_backend(): Backend\AbstractBackend
    {
        return $this->backend;
    }

    /**
     * Retrns true when the field cache has warm cache hit
     */
    function has_hit(): bool
    {
        return $this->cached_value instanceof CachedValue;
    }
}