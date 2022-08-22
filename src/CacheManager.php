<?php

declare(strict_types=1);

namespace WPGraphQL\Extensions\Cache;

class CacheManager
{

    static $query_cache = null;

    static $backend = null;

    static $initialized = false;

    static function init()
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        add_action('graphql_init', [self::class, '__action_graphql_init']);
    }

    static function __action_graphql_init()
    {
        MeasurePerformance::init();
        /***
         * Initialize the default backend
         */
        self::$backend = new Backend\Redis();
        $query_cache = new QueryCache([]);
        self::$query_cache = $query_cache;
        $query_cache->activate(self::$backend);
    }
}

if (class_exists('\WP_CLI')) {
    WPCLICommand::init();
}
