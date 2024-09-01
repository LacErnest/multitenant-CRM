<?php

namespace App\Traits\Models;

use Illuminate\Support\Str;

trait Uuids
{
    public function initializeUuids()
    {
        $this->keyType = 'string';
    }

    public function getIdAttribute($id)
    {
        return (string)$id;
    }

    public function getIncrementing()
    {
        return false;
    }

    /**
     * Boot function from laravel.
     */
    protected static function bootUuids()
    {
        static::creating(function ($model) {
            $model->incrementing = false;
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid();
            }
        });
    }
}
