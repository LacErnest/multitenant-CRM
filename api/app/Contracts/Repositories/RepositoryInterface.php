<?php

namespace App\Contracts\Repositories;

use Closure;

use Exception;

/**
 * Interface Repository
 */
interface RepositoryInterface
{
    /**
     * @param  array $data
     *
     * @return mixed
     */
    public function create(array $data);

    /**
     * @param  array $data
     *
     * @return mixed
     */
    public function createMany(array $data);

    /**
     * @param  mixed $id
     * @param  bool  $force
     *
     * @return mixed
     */
    public function delete($id, bool $force = false);

    /**
     * @param  mixed $id
     * @param  array $data
     * @param  bool $onlyFillable
     *
     * @return mixed
     */
    public function update($id, array $data, bool $onlyFillable = true);

    /**
     * @param  string $key
     * @param  mixed  $value
     * @param  array  $data
     * @param  bool   $onlyFillable
     *
     * @return mixed
     */
    public function updateBy(string $key, $value, array $data, bool $onlyFillable = true);

    /**
     * @param  array        $ids
     * @param  array        $data
     * @param  Closure|null $where
     *
     * @return mixed
     */
    public function massUpdate(array $ids, array $data, ?Closure $where = null);

    /**
     * @param  mixed          $id
     * @param  array          $with
     * @param  array|string[] $columns
     * @param  bool           $withTrashed
     *
     * @return mixed
     */
    public function firstById($id, array $with = [], array $columns = ['*'], $withTrashed = false);

    /**
     * @param  string         $key
     * @param  mixed          $value
     * @param  array          $with
     * @param  array|string[] $columns
     * @param  bool           $withTrashed
     *
     * @return mixed
     */
    public function firstBy(
        string $key,
        $value,
        array $with = [],
        array $columns = ['*'],
        $withTrashed = false
    );

    /**
     * @param  mixed          $id
     * @param  array          $with
     * @param  array|string[] $columns
     * @param  bool           $withTrashed
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function firstByIdOrNull(
        $id,
        array $with = [],
        array $columns = ['*'],
        $withTrashed = false
    );

    /**
     * @param  string         $key
     * @param  mixed          $value
     * @param  array          $with
     * @param  array|string[] $columns
     * @param  bool           $withTrashed
     *
     * @return mixed
     */
    public function firstByOrNull(
        string $key,
        $value,
        array $with = [],
        array $columns = ['*'],
        $withTrashed = false
    );

    /**
     * @param  array $ids
     * @param  array $with
     *
     * @return mixed
     */
    public function getByIds(array $ids, array $with = []);

    /**
     * @param  array          $with
     * @param  array|string[] $columns
     *
     * @return mixed
     */
    public function getAll(array $with = [], array $columns = ['*']);

    /**
     * @param  string         $key
     * @param  mixed          $value
     * @param  array          $with
     * @param  array|string[] $columns
     *
     * @return mixed
     */
    public function getAllBy(string $key, $value, array $with = [], array $columns = ['*']);

    /**
     * @param int $limit
     * @param int $offset
     * @param array $with
     * @param array $columns
     * @param string $sort
     * @param string $direction
     * @return mixed
     */
    public function getAllWithTrashed(
        int $limit = 10,
        int $offset = 0,
        array $with = [],
        array $columns = ['*'],
        string $sort = 'created_at',
        string $direction = 'asc'
    );
}
