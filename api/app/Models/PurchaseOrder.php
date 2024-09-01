<?php

namespace App\Models;

use App\Http\Resources\PurchaseOrder\PurchaseOrderResource;
use App\Services\Export\Interfaces\CanBeExported;
use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;
use Spatie\MediaLibrary\InteractsWithMedia;

class PurchaseOrder extends Model implements CanBeExported
{
    use uuids;
    use OnTenant;
    use InteractsWithMedia;
    use AutoElastic;

    protected $fillable = [
        'xero_id',
        'project_id',
        'resource_id',
        'date',
        'delivery_date',
        'status',
        'number',
        'reference',
        'currency_code',
        'total_price',
        'total_vat',
        'total_price_usd',
        'total_vat_usd',
        'currency_rate_company',
        'currency_rate_customer',
        'penalty',
        'penalty_type',
        'pay_date',
        'reason_of_rejection',
        'reason_of_penalty',
        'rating','reason',
        'manual_input',
        'manual_price',
        'manual_vat',
        'currency_rate_resource',
        'legal_entity_id',
        'payment_terms',
        'vat_status',
        'vat_percentage',
        'authorised_date',
        'created_by',
        'authorised_by',
        'processed_by',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'date',
        'delivery_date',
        'pay_date',
        'authorised_date',
    ];

    protected $casts = [
        'total_price' => 'float',
        'total_price_usd' => 'float',
        'total_vat' => 'float',
        'total_vat_usd' => 'float',
        'currency_rate_company' => 'float',
        'currency_rate_customer' => 'float',
        'manual_price' => 'float',
        'manual_vat' => 'float',
        'currency_rate_resource' => 'float',
        'manual_input' => 'boolean',
        'vat_percentage' => 'float',
    ];

    protected $resourceClass = PurchaseorderResource::class;

    protected $indexSettings = [
        'analysis' => [
            'normalizer' => [
                'to_lowercase' => [
                    'type' => 'custom',
                    'filter' => ['lowercase']
                ]
            ]
        ]
    ];

    protected $mappingProperties = [
        'id' => [
            'type' => 'keyword',
        ],
        'xero_id' => [
            'type' => 'keyword',
        ],
        'project_info' => [
            'type' => 'nested',
            'properties' => [
                'id' => [
                    'type' => 'keyword'
                ],
                'po_cost' => [
                    'type' => 'float'
                ],
                'po_vat' => [
                    'type' => 'float'
                ],
                'employee_cost' => [
                    'type' => 'float'
                ],
                'po_cost_usd' => [
                    'type' => 'float'
                ],
                'po_vat_usd' => [
                    'type' => 'float'
                ],
                'employee_cost_usd' => [
                    'type' => 'float'
                ]
            ]
        ],
        'project_id' => [
            'type' => 'keyword',
        ],
        'project' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'customer_id' => [
            'type' => 'keyword',
        ],
        'customer' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'contact_id' => [
            'type' => 'keyword'
        ],
        'contact' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'resource_id' => [
            'type' => 'keyword',
        ],
        'resource' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'order_id' => [
            'type' => 'keyword',
        ],
        'order' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'authorised_date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'delivery_date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'pay_date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'status' => [
            'type' => 'keyword',
        ],
        'number' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'reference' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'reason_of_rejection' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'reason_of_penalty' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'currency_code' => [
            'type' => 'keyword',
        ],
        'customer_currency_code' => [
            'type' => 'keyword',
        ],
        'total_price' => [
            'type' => 'float',
        ],
        'total_vat' => [
            'type' => 'float',
        ],
        'total_price_usd' => [
            'type' => 'float',
        ],
        'total_vat_usd' => [
            'type' => 'float',
        ],
        'customer_total_price' => [
            'type' => 'float',
        ],
        'penalty' => [
            'type' => 'integer'
        ],
        'created_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'updated_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'legal_entity_id' => [
            'type' => 'keyword',
        ],
        'legal_entity' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'shadow_price' => [
            'type' => 'float',
        ],
        'shadow_vat' => [
            'type' => 'float',
        ],
        'shadow_price_usd' => [
            'type' => 'float',
        ],
        'shadow_vat_usd' => [
            'type' => 'float',
        ],
        'is_contractor' => [
            'type' => 'boolean',
        ],
        'purchase_order_project' => [
            'type' => 'boolean',
        ]
    ];

    /** RELATIONS */
    /**
     * @return BelongsTo
     */
    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class, 'legal_entity_id');
    }

    public function invoices()
    {
        return $this->hasMany('App\Models\Invoice');
    }

    public function project()
    {
        return $this->belongsTo('App\Models\Project');
    }

    public function resource()
    {
        return $this->belongsTo('App\Models\Resource');
    }

    public function items()
    {
        return $this->morphMany('App\Models\Item', 'entity');
    }

    public function priceModifiers()
    {
        return $this->morphMany('App\Models\PriceModifier', 'entity');
    }

    public function creator(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function authorizer(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'authorised_by');
    }

    public function processor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'processed_by');
    }

    public static function getResource()
    {
        return (new static)->resourceClass;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('export_purchase_order')->useDisk('exports')->singleFile();
    }

    public function getTemplatePath($template_id): string
    {
        return Template::findOrFail($template_id)->getMedia('templates_purchase_order')->first()->getPath();
    }

    public function getExportPath()
    {
        return $this->exportPath = Storage::disk('exports')->path('').'output.docx';
    }

    public function getExportMediaCollection(): string
    {
        return 'export_purchase_order';
    }

    public function saveQuietly(array $options = [])
    {
        return static::withoutEvents(function () use ($options) {
            return $this->save($options);
        });
    }
}
