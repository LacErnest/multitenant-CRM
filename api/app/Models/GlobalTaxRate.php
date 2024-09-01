<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Class GlobalTaxRate
 * Model used to handle tax rates of legal entities
 */
class GlobalTaxRate extends Model
{
    use Uuids;

    protected $connection = 'mysql';

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
        'belonged_to_company_id',
        'legal_entity_id',
    ];

    protected $dates = [
        'start_date',
        'end_date',
    ];
}
