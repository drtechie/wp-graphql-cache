<?php

declare(strict_types=1);

namespace WPGraphQL\Extensions\Cache;

class WPCLICommand
{
    static function init()
    {
        \WP_CLI::add_command('graphql-cache', self::class);
    }

    /**
     * Clears cache
     *
     * ## EXAMPLES
     *
     *     wp graphql-cache clear
     *
     */
    public function clear($args, $assoc_args)
    {
        CacheManager::clear();
        \WP_CLI::success('All WPGraphQL Cache Zones cleared');
    }
}
