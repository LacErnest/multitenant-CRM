<?php


namespace App\Repositories\Cache;

interface CacheRepositoryInterface
{

    /**
     * Get all cached data
     */
    public function all();

    /**
     * Find cached data for unique id
     */
    public function find($id);
}
