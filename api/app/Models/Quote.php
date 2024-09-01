<?php

namespace App\Models;

use App\Http\Resources\Quote\QuoteResource;
use App\Services\Export\Interfaces\CanBeExported;
use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;
use Spatie\MediaLibrary\InteractsWithMedia;

class Quote extends Model implements CanBeExported
{
    use uuids;
    use OnTenant;
    use AutoElastic;
    use InteractsWithMedia;

    protected $fillable = [
        'xero_id',
        'project_id',
        'date',
        'expiry_date',
        'status',
        'number',
        'reference',
        'reason_of_refusal',
        'currency_code',
        'total_price',
        'total_vat',
        'total_price_usd',
        'total_vat_usd',
        'currency_rate_company',
        'currency_rate_customer',
        'manual_input',
        'manual_price',
        'manual_vat',
        'down_payment',
        'legal_entity_id',
        'master',
        'shadow',
        'down_payment_type',
        'vat_status',
        'vat_percentage',
    ];

    protected $resourceClass = QuoteResource::class;

    protected $dates = [
        'created_at',
        'updated_at',
        'date',
        'expiry_date',
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
        'manual_input' => 'boolean',
        'master' => 'boolean',
        'shadow' => 'boolean',
        'vat_percentage' => 'float',
    ];

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
        'date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'expiry_date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'status' => [
            'type' => 'keyword',
        ],
        'currency_rate_customer'=> [
            'type' => 'keyword',
        ],
        'reason_of_refusal' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
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
        'currency_code' => [
            'type' => 'keyword',
        ],
        'customer_currency_code' => [
            'type' => 'keyword',
        ],
        'total_price' => [
            'type' => 'float',
        ],
        'total_price_usd' => [
            'type' => 'float',
        ],
        'customer_total_price' => [
            'type' => 'float',
        ],
        'total_vat' => [
            'type' => 'float',
        ],
        'total_vat_usd' => [
            'type' => 'float',
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
        'order_total_price' => [
            'type' => 'float',
        ],
        'order_delivered_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'master' => [
            'type' => 'boolean',
        ],
        'shadow' => [
            'type' => 'boolean',
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
        'sales_person_id' => [
            'type' => 'keyword',
        ],
        'sales_person' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'second_sales_person_id' => [
            'type' => 'keyword',
        ],
        'second_sales_person' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
    ];

    /** RELATIONS */
    /**
     * @return BelongsTo
     */
    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class, 'legal_entity_id');
    }

    public function project()
    {
        return $this->belongsTo('App\Models\Project');
    }

    public function items()
    {
        return $this->morphMany('App\Models\Item', 'entity');
    }

    public function priceModifiers()
    {
        return $this->morphMany('App\Models\PriceModifier', 'entity')->orderBy('created_at', 'ASC');
    }

    public function salesPersons(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\User', getTenantConnectionName().'.'.'quote_sales_persons')->withTimestamps()->withPivot('id');
    }

    public static function getResource()
    {
        return (new static)->resourceClass;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('export_quote')
            ->useDisk('exports')
            ->singleFile();

        $this->addMediaCollection('document_quote')
            ->acceptsMimeTypes([
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword',
            ])
            ->useDisk('quotes')
            ->singleFile();
    }

    public function getTemplatePath($template_id): string
    {
        return Template::findOrFail($template_id)->getMedia('templates_quote')->first()->getPath();
    }

    public function getExportPath(): string
    {
        return $this->exportPath = Storage::disk('exports')->path('').'output.docx';
    }

    public function getExportMediaCollection(): string
    {
        return 'export_quote';
    }

    public function saveQuietly(array $options = [])
    {
        return static::withoutEvents(function () use ($options) {
            return $this->save($options);
        });
    }
}
