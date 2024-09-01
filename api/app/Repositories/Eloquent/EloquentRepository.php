<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\RepositoryInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class EloquentRepository
 *
 * Base implementation of RepositoryInterface for Eloquent
 */
abstract class EloquentRepository implements RepositoryInterface
{
    /**
     * Current Model
     */
    public const CURRENT_MODEL = Model::class;

    /**
     * @var Builder $builder
     */
    protected Builder $builder;

    /**
     * @param array $data
     *
     * @return Model
     */
    public function create(array $data)
    {
        return $this->builder()->create($data);
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function createMany(array $data)
    {
        return $this->builder()->insert($data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id, bool $force = false)
    {
        if ($force) {
            $pk = $this->builder()->getModel()->getKeyName();

            return $this->builder()->where($pk, '=', $id)->forceDelete();
        }

        return $this->builder()->getModel()->destroy($id);
    }

    /**
     * {@inheritDoc}
     */
    public function update($id, array $data, bool $onlyFillable = true)
    {
        $primaryKey = $this->builder()->getModel()->getKeyName();

        return $this->updateBy($primaryKey, $id, $data, $onlyFillable);
    }

    /**
     * {@inheritDoc}
     */
    public function updateBy(string $key, $value, array $data, bool $onlyFillable = true)
    {
        $all = $this->getAllBy($key, $value);

        $saved = [];

        foreach ($all as $model) {
            $onlyFillable
            ? $model->fill($data)
            : $model->forceFill($data);
            $saved[] = $model->save();
        }

        return $saved;
    }

    /**
     * {@inheritdoc}
     */
    public function massUpdate(array $ids, array $data, Closure $where = null)
    {
        $primaryKey = $this->builder()->getModel()->getKeyName();

        return $this->builder()->whereIn($primaryKey, $ids)->where($where)->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function firstById($id, array $with = [], array $columns = ['*'], $withTrashed = false)
    {
        $primaryKey = $this->builder()->getModel()->getKeyName();

        return $this->firstBy($primaryKey, $id, $with, $columns, $withTrashed);
    }

    /**
     * {@inheritdoc}
     */
    public function firstBy(
        string $key,
        $value,
        array $with = [],
        array $columns = ['*'],
        $withTrashed = false
    ) {
        $query = $this->builder()
          ->getModel()
          ->where($key, '=', $value)
          ->with($with)
          ->select($columns);

        if ($withTrashed && in_array(SoftDeletes::class, class_uses($this->builder()->getModel()))) {
            /** @var Builder|Model|SoftDeletes $query */
            $query->withTrashed();
        }

        return $query->firstOrFail();
    }

    /**
     * {@inheritdoc}
     */
    public function firstByIdOrNull(
        $id,
        array $with = [],
        array $columns = ['*'],
        $withTrashed = false
    ) {
        try {
            return $this->firstById($id, $with, $columns, $withTrashed);
        } catch (ModelNotFoundException $exception) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function firstByOrNull(
        string $key,
        $value,
        array $with = [],
        array $columns = ['*'],
        $withTrashed = false
    ) {
        try {
            return $this->firstBy($key, $value, $with, $columns, $withTrashed);
        } catch (ModelNotFoundException $exception) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getByIds(array $ids, array $with = [])
    {
        return $this->builder()
          ->whereIn('id', $ids)
          ->with($with)
          ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(array $with = [], array $columns = ['*'])
    {
        return $this->builder()
          ->with($with)
          ->select($columns)
          ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllBy(string $key, $value, array $with = [], array $columns = ['*'])
    {
        return $this->builder()->where($key, '=', $value)
          ->with($with)
          ->select($columns)
          ->get();
    }

    /**
     * @return Builder
     */
    protected function builder()
    {
        return call_user_func([static::CURRENT_MODEL, 'query']);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWithTrashed(
        int $limit = 10,
        int $offset = 0,
        array $with = [],
        array $columns = ['*'],
        string $sort = 'created_at',
        string $direction = 'asc'
    ) {
        return $this->builder()
          ->withTrashed()
          ->orderBy($sort, $direction)
          ->offset($offset)
          ->limit($limit)
          ->with($with)
          ->select($columns)
          ->get();
    }
}
