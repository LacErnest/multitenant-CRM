<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

/**
 * Class TaxRate
 * @package App\Models
 * Model is used to get the tax rate of Ireland for a specific time period
 */
class TaxRate extends Model
{
    use Uuids,
        OnTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tax_rate',
        'start_date',
        'end_date',
        'xero_sales_tax_type',
        'xero_purchase_tax_type',
    ];

    protected $dates = [
        'start_date',
        'end_date',
    ];
}
