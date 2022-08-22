<?php

declare(strict_types=1);

namespace WPGraphQL\Extensions\Cache\Backend;
use WPGraphQL\Extensions\Cache\CachedValue;

class Redis extends AbstractBackend
{
    function set(
        string $key,
        CachedValue $cached_value,
        string $cache_group,
        $expire = null
    ): void {
        $contents = serialize($cached_value);
        wp_cache_set($key, $contents, $cache_group, $expire);
    }

    function get(string $key, string $cache_group): ?CachedValue
    {
        $contents = wp_cache_get($key, $cache_group);
        if (empty($contents)) {
            return null;
        }

        $cached_value = unserialize($contents);

        if ($cached_value instanceof CachedValue) {
            return $cached_value;
        }

        return null;
    }

    function delete(string $key): void
    {
        $this->client->delete($key);
    }

    function clear($cache_group): bool
    {
        return wp_cache_delete_group($cache_group);
    }
}
