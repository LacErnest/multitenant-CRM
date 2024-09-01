<?php


namespace App\Repositories\Cache;

/**
 * The CacheRepository class provides a set of methods for managing cache.
 * It implements the CacheRepositoryInterface and offers functionality for
 * retrieving, finding, and generating keys for cached data.
 */
class CacheRepository implements CacheRepositoryInterface
{
    /**
     * @var string
     */
    protected $seperator = '.';
    /**
     * @var string
     */
    protected $duration = 10;

    /**
     * Get all cached data
     */
    public function all()
    {
        //
    }

    /**
     * Find cached data for unique id
     */
    public function find($id)
    {
        //
    }

    /**
     * Get unique identifier for key in cache
     * @param string $name
     * @param array $params
     * @return string
     */
    public function getKey(string $name, array $params = []): string
    {
        foreach ($params as $index => $param) {
            if (is_array($param)) {
                // Sort the sales persons array to normalize the order
                sort($param);

                // Generate a hash of the serialized array
                $uniqueKey = md5(serialize($param));
                $params[$index] = $uniqueKey;
            }
        }
        return implode($this->seperator, array_merge([$name], $params));
    }

    protected function getDuration()
    {
        return env('CACHE_DURATION') ?: $this->duration;
    }
}
