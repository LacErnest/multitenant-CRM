<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class EarnOutStatus extends Model
{
    use Uuids,
        OnTenant;

    protected $fillable = [
        'quarter',
        'approved',
        'confirmed',
        'received',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'approved',
        'confirmed',
        'received',
    ];
}
