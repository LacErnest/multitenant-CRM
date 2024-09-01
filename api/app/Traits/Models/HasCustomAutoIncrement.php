<?php

namespace App\Traits\Models;

use App\Enums\TablePreferenceType;
use App\Models\AutoIncrement;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;

trait HasCustomAutoIncrement
{
    /**
     * @var AutoIncrement
     */
    private static $lastIncrement;

    public static function bootHasCustomAutoIncrement()
    {
        static::creating(function ($model) {
            $self = new static();
            if (empty($self->autoEntity)) {
                throw new \Exception('autoEntity  not found');
            } elseif (empty($self->autoAttribute)) {
                throw new \Exception('autoAttribute not found');
            }

            $entity = TablePreferenceType::make($self->autoEntity)->getIndex();

            $company = Company::find(getTenantWithConnection());

            if (!empty($company)) {
                static::$lastIncrement = $company->increments()->where('entity', $entity)->first();
            } else {
                static::$lastIncrement = AutoIncrement::whereNull('company_id')->where('entity', $entity)->first();
            }

            $lastValue = static::$lastIncrement->number ?? 0;
            $model->{$self->autoAttribute} = $lastValue + 1;
        });

        static::created(function ($model) {
            $company = Company::find(getTenantWithConnection());
            $self = new static();
            if (!empty(static::$lastIncrement)) {
                static::$lastIncrement->update(['number' => $model->{$self->autoAttribute}]);
                return;
            }
            $entity = TablePreferenceType::make($self->autoEntity)->getIndex();
            AutoIncrement::create([
              'company_id' => $company->id ?? null,
              'number' => $model->{$self->autoAttribute},
              'entity' => $entity,
            ]);
            static::$lastIncrement = null;
        });
    }
}
