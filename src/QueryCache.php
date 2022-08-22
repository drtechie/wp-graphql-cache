<?php

declare(strict_types=1);

namespace WPGraphQL\Extensions\Cache;

use WPGraphQL\Extensions\Cache\Backend\AbstractBackend;
use GraphQL\Server\Helper;

/**
 * Class that takes care of caching of full queries
 */
class QueryCache extends AbstractCache
{
    /**
     * Cache group for setting caches
     */
    protected $cache_group = null;

    function __construct($config)
    {
        parent::__construct($config = []);
        $this->cache_group = $config['cache_group'] ?? 'graphqlcache';
    }

    /**
     * Activate the cache with the given backend if the cache did not have own
     * custom backend.
     */
    function activate(AbstractBackend $backend)
    {
        if (!$this->backend) {
            $this->backend = $backend;
        }

        add_action(
            'init_graphql_request',
            [$this, '__action_do_graphql_request'],
            1
        );

        add_action(
            'graphql_process_http_request_response',
            [$this, '__action_graphql_process_http_request_response'],
            // Use large value as this should be the last response filter
            // because we want to save the last version of the response to the
            // cache.
            1000,
            5
        );

        add_action('graphql_response_set_headers', [
            $this,
            '__action_graphql_response_set_headers',
        ]);

        add_action('save_post', [
            $this,
            '__action_delete_cache_group',
        ]);
    }

    function __action_do_graphql_request() {
        $helper       = new Helper();
		$params = $helper->parseHttpRequest();
        if (empty($params)) {
            return;
        }

        $query = $params->query;
        $variables = $params->variables;

        if (empty($query)) {
            return;
        }

        $args_hash = empty($variables)
            ? 'null'
            : Utils::hash(Utils::stable_string($variables));

        $query_hash = Utils::hash($query);

        $this->key = "{$query_hash}-${args_hash}";

        $this->read_cache();

        if ($this->has_hit()) {
            // Respond from the cache as early as possible to avoid graphql
            // query parsing etc.
            Utils::log('HIT query cache');
            $this->respond_and_exit();
        }
    }

    function __action_graphql_response_set_headers()
    {
        // Just add MISS header if we have match and have not already exited
        // with the cached response. respond_and_exit() handles the HIT header
        header('x-graphql-query-cache: MISS');
    }

    function __action_graphql_process_http_request_response(
        $response,
        $result,
        $operation_name,
        $query,
        $variables
    ) {
        if (!empty($response->errors)) {
            return;
        }

        // Save results as pre encoded json
        $this->backend->set(
            $this->get_cache_key(),
            new CachedValue(wp_json_encode($response)),
            $this->cache_group,
            $this->expire
        );
        Utils::log('Writing QueryCache ' . $this->key);
    }

    function __action_delete_cache_group()
    {
        return $this->backend->clear($this->cache_group);
    }

    function respond_and_exit()
    {
        header(
            'Content-Type: application/json; charset=' .
                get_option('blog_charset')
        );
        header('x-graphql-query-cache: HIT');

        do_action('graphql_cache_early_response');

        // We stored the encoded JSON string so we can just respond with it here
        echo $this->get_cached_data();
        die();
    }
}
