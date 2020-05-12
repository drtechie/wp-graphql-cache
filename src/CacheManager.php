<?php

declare(strict_types=1);

namespace WPGraphQL\Extensions\Cache;

class CacheManager
{
    static $fields = [];

    static $query_caches = [];

    static $backend = null;

    static $initialized = false;

    static function init()
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        add_action('graphql_response_set_headers', [
            self::class,
            '__action_graphql_response_set_headers',
        ]);

        add_action('graphql_init', [self::class, '__action_graphql_init']);
    }

    static function __action_graphql_init()
    {
        MeasurePerformance::init();

        /***
         * Initialize the default backend
         */
        self::$backend = apply_filters(
            'graphql_cache_backend',
            new Backend\FileSystem()
        );

        $is_active = apply_filters('graphql_cache_active', true);

        if (!$is_active) {
            return;
        }

        foreach (self::$fields as $field) {
            $field->activate(self::$backend);
        }

        foreach (self::$query_caches as $query_cache) {
            $query_cache->activate(self::$backend);
        }
    }

    static function register_graphql_field_cache($config)
    {
        if (empty($config['backend'])) {
            $config['backend'] = self::$backend;
        }

        $field = new FieldCache($config);
        self::$fields[] = $field;

        if (did_action('graphql_init')) {
            $field->activate(self::$backend);
        }

        return $field;
    }

    static function register_graphql_query_cache($config)
    {
        if (empty($config['backend'])) {
            $config['backend'] = self::$backend;
        }

        $query_cache = new QueryCache($config);
        self::$query_caches[] = $query_cache;

        if (did_action('graphql_init')) {
            $query_cache->activate(self::$backend);
        }

        return $query_cache;
    }

    /**
     * Set cache status headers for the field caches
     */
    static function __action_graphql_response_set_headers()
    {
        $value = [];

        foreach (self::$fields as $field) {
            if (!$field->has_match()) {
                continue;
            }

            if ($field->has_hit()) {
                $value[] = 'HIT:' . $field->get_field_name();
            } else {
                $value[] = 'MISS:' . $field->get_field_name();
            }
        }

        if (empty($value)) {
            return;
        }

        $value = implode(', ', $value);

        header("x-graphql-field-cache: $value");
    }

    static function clear_zone(string $zone): bool
    {
        return self::$backend->clear_zone($zone);
    }

    static function clear(): bool
    {
        return self::$backend->clear();
    }
}

function register_graphql_field_cache($config)
{
    CacheManager::register_graphql_field_cache($config);
}

if (class_exists('\WP_CLI')) {
    WPCLICommand::init();
}
