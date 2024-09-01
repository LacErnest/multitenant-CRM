<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class TablePreference extends Model
{
    use Uuids;
    use OnTenant;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'type', 'key', 'columns', 'sorts', 'filters'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
