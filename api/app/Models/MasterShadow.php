<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;

class MasterShadow extends Model
{
    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'master_id',
        'shadow_id',
        'master_company_id',
        'shadow_company_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];
}
