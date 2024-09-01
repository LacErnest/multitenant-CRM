<?php

namespace App\Models;

use App\Http\Resources\LegalEntity\LegalEntityResource;
use App\Models\LegalEntityNotificationSetting;
use App\LegalEntityTemplate;
use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class LegalEntity
 * This model is used to store, and manage legal entities that could be used by different companies.
 * All companies can use those legal entities to set up their documents, Xero configuration
 *
 * @property string name
 * @property string vat_number
 * @property string address_id
 * @property string european_bank_id
 * @property string american_bank_id
 * @property string legal_entity_xero_config_id
 * @property string legal_entity_setting_id
 * @property string usdc_wallet_address
 *
 */
class LegalEntity extends Model
{
    use Uuids,
        AutoElastic,
        SoftDeletes;

    protected $connection = 'mysql';

    protected $resourceClass = LegalEntityResource::class;

    protected $fillable = [
        'name',
        'vat_number',
        'address_id',
        'legal_entity_xero_config_id',
        'legal_entity_setting_id',
        'european_bank_id',
        'american_bank_id',
        'usdc_wallet_address'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $indexSettings = [
        'analysis' => [
            'normalizer' => [
                'to_lowercase' => [
                    'type'   => 'custom',
                    'filter' => [
                        'lowercase',
                    ],
                ],
            ],
        ],
    ];

    protected $mappingProperties = [
        'id' => [
            'type' => 'keyword',
        ],
        'name' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'vat_number' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'european_bank_name' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase',
                ],
            ],
        ],
        'european_bank_iban' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase',
                ],
            ],
        ],
        'european_bank_bic' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase',
                ],
            ],
        ],
        'american_bank_account_number' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase',
                ],
            ],
        ],
        'american_bank_routing_number' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase',
                ],
            ],
        ],
        'address_id' => [
            'type' => 'keyword',
        ],
        'addressline_1' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'addressline_2' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'city' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'region' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'postal_code' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'country' => [
            'type' => 'keyword',
        ],
        'european_bank_id' => [
            'type' => 'keyword',
        ],
        'european_bank_address_id' => [
            'type' => 'keyword',
        ],
        'european_bank_addressline_1' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'european_bank_addressline_2' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'european_bank_city' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'european_bank_region' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'european_bank_postal_code' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'european_bank_country' => [
            'type' => 'keyword',
        ],
        'american_bank_id' => [
            'type' => 'keyword',
        ],
        'american_bank_name' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase',
                ],
            ],
        ],
        'american_bank_address_id' => [
            'type' => 'keyword',
        ],
        'american_bank_addressline_1' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'american_bank_addressline_2' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'american_bank_city' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'american_bank_region' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'american_bank_postal_code' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'american_bank_country' => [
            'type' => 'keyword',
        ],
        'usdc_wallet_address' => [
            'type' => 'keyword',
        ],
        'created_at' => [
            'type'   => 'date',
            'format' => 'epoch_second',
        ],
        'updated_at' => [
            'type'   => 'date',
            'format' => 'epoch_second',
        ],
        'deleted_at' => [
            'type'   => 'date',
            'format' => 'epoch_second',
        ],
    ];

    /**
     * @return string
     */
    public static function getResource()
    {
        return (new static)->resourceClass;
    }

    /** RELATIONS */
    /**
     * @return BelongsTo
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'address_id');
    }

    /**
     * @return BelongsTo
     */
    public function europeanBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'european_bank_id');
    }

    /**
     * @return BelongsTo
     */
    public function americanBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'american_bank_id');
    }

    /**
     * @return BelongsToMany
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_legal_entity')
            ->withTimestamps()->withPivot('id', 'default', 'local');
    }

    /**
     * @return BelongsTo
     */
    public function xeroConfig(): BelongsTo
    {
        return $this->belongsTo(LegalEntityXeroConfig::class, 'legal_entity_xero_config_id');
    }

    /**
     * @return BelongsTo
     */
    public function legalEntitySetting(): BelongsTo
    {
        return $this->belongsTo(LegalEntitySetting::class, 'legal_entity_setting_id');
    }

    /**
     * @return BelongsTo
     */
    public function legalEntityNotificationSetting(): HasOne
    {
        return $this->hasOne(LegalEntityNotificationSetting::class, 'legal_entity_id');
    }

    /**
     * @return BelongsTo
     */
    public function legalEntityTemplate(): HasOne
    {
        return $this->hasOne(LegalEntityNotificationSetting::class, 'legal_entity_id');
    }

    /**
     * @return HasMany
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'legal_entity_id');
    }

    /**
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'legal_entity_id');
    }

    /**
     * @return HasMany
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'legal_entity_id');
    }

    /**
     * @return HasMany
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'legal_entity_id');
    }

    /**
     * @return HasMany
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'legal_entity_id');
    }

    /**
     * @return HasMany
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class, 'legal_entity_id');
    }
}
