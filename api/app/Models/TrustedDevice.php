<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;

class TrustedDevice extends Model
{
    use Uuids;

    protected $connection = 'mysql';

    protected $fillable = [
        'user_id',
        'user_agent',
        'ip_address',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];
}
