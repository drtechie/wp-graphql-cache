<?php

declare(strict_types=1);

namespace WPGraphQL\Extensions\Cache;

/**
 * Value container for cached values to distinguish them from null and false.
 *
 * Also stores the creation time for PHP based expiration checks.
 */
class CachedValue
{
    /**
     * The raw cached data
     */
    private $data = null;

    function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the raw data
     */
    function get_data()
    {
        return $this->data;
    }
}
