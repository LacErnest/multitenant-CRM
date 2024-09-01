<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bank extends Model
{
    use Uuids;

    protected $connection = 'mysql';

    protected $fillable = [
        'name',
        'iban',
        'bic',
        'account_number',
        'routing_number',
        'usa_account_number',
        'usa_routing_number',
        'address_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $with = [
        'address'
    ];

    /** RELATIONS */
    /**
     * @return BelongsTo
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'address_id');
    }
}
