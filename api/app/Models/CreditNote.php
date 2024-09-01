<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class CreditNote extends Model
{
    use Uuids,
        OnTenant;

    protected $fillable = [
        'xero_id',
        'project_id',
        'invoice_id',
        'type',
        'date',
        'status',
        'number',
        'reference',
        'currency_code',
        'total_price',
        'total_vat',
        'total_price_usd',
        'total_vat_usd',
    ];

    protected $dates = [
        'date',
        'created_at',
        'updated_at',
    ];


    /** RELATIONS */
    /**
     * @return BelongsTo
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function items()
    {
        return $this->morphMany('App\Models\Item', 'entity');
    }
}
