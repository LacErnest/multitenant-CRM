<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CompanySetting
 * Model used to set companies settings
 *
 * @property float max_commission_percentage
 * @property string sales_person_commission_limit
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class CompanySetting extends Model
{
    use Uuids, SoftDeletes;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'company_id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $connection = 'mysql';

    protected $fillable = [
        'company_id',
        'max_commission_percentage',
        'vat_default_value',
        'vat_max_value',
        'sales_person_commission_limit',
        'project_management_default_value',
        'project_management_max_value',
        'special_discount_default_value',
        'special_discount_max_value',
        'director_fee_default_value',
        'director_fee_max_value',
        'transaction_fee_default_value',
        'transaction_fee_max_value'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'max_commission_percentage' => 'decimal:2',
        'vat_default_value' => 'decimal:2',
        'vat_max_value' => 'decimal:2',
        'project_management_default_value' => 'decimal:2',
        'project_management_max_value' => 'decimal:2',
        'special_discount_default_value' => 'decimal:2',
        'special_discount_max_value' => 'decimal:2',
        'director_fee_default_value' => 'decimal:2',
        'director_fee_max_value' => 'decimal:2',
        'transaction_fee_default_value' => 'decimal:2',
        'transaction_fee_max_value' => 'decimal:2',
    ];
}
