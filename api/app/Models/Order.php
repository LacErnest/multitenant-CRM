<?php

namespace App\Models;

use App\Http\Resources\Order\OrderResource;
use App\Services\Export\Interfaces\CanBeExported;
use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\InteractsWithMedia;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

/**
 * Class Order
 *
 * @property string $id
 * @property string $project_id
 * @property string $quote_id
 * @property Carbon $date
 * @property Carbon $deadline
 * @property Carbon $delivered_at
 * @property int    $status
 * @property int    $manual_input
 * @property string $number
 * @property string $reference
 * @property int    $currency_code
 * @property float  $total_price
 * @property float  $total_vat
 * @property float  $total_price_usd
 * @property float  $total_vat_usd
 * @property float  $manual_price
 * @property float  $manual_vat
 * @property float  $currency_rate_company
 * @property float  $currency_rate_customer
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property mixed invoices
 * @property mixed project
 */
class Order extends Model implements CanBeExported
{
    use uuids;
    use OnTenant;
    use AutoElastic;
    use InteractsWithMedia;

    protected $fillable = [
        'project_id',
        'quote_id',
        'date',
        'deadline',
        'delivered_at',
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
        'manual_input',
        'manual_price',
        'manual_vat',
        'legal_entity_id',
        'master',
        'shadow',
        'vat_status',
        'vat_percentage',
    ];

    protected $resourceClass = OrderResource::class;

    protected $dates = [
        'created_at',
        'updated_at',
        'date',
        'deadline',
        'delivered_at',
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
                ],
                'external_employee_cost' => [
                    'type' => 'float'
                ],
                'external_employee_cost_usd' => [
                    'type' => 'float'
                ],
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
        'project_manager_id' => [
            'type' => 'keyword',
        ],
        'project_manager' => [
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
        'quote_id' => [
            'type' => 'keyword',
        ],
        'quote' => [
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
        'deadline' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'delivered_at' => [
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
        'currency_code' => [
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
        'gross_margin' => [
            'type' => 'float',
        ],
        'gross_margin_usd' => [
            'type' => 'float',
        ],
        'customer_gross_margin' => [
            'type' => 'float',
        ],
        'costs' => [
            'type' => 'float',
        ],
        'costs_usd' => [
            'type' => 'float',
        ],
        'customer_costs' => [
            'type' => 'float',
        ],
        'potential_costs' => [
            'type' => 'float',
        ],
        'potential_costs_usd' => [
            'type' => 'float',
        ],
        'customer_potential_costs' => [
            'type' => 'float',
        ],
        'potential_gm' => [
            'type' => 'float',
        ],
        'potential_gm_usd' => [
            'type' => 'float',
        ],
        'customer_potential_gm' => [
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
    ];

    /** RELATIONS */

    /**
     * @return HasMany
     */
    public function shadows(): HasMany
    {
        return $this->hasMany(MasterShadow::class, 'master_id');
    }

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

    public function items()
    {
        return $this->morphMany('App\Models\Item', 'entity');
    }

    public function quote()
    {
        return $this->belongsTo('App\Models\Quote');
    }

    public function priceModifiers()
    {
        return $this->morphMany('App\Models\PriceModifier', 'entity')->orderBy('created_at', 'ASC');
    }

    public static function getResource()
    {
        return (new static)->resourceClass;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('export_order')
            ->useDisk('exports')
            ->singleFile();

        $this->addMediaCollection('document_order')
            ->acceptsMimeTypes([
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword',
            ])
            ->useDisk('orders')
            ->singleFile();
    }

    public function getTemplatePath($template_id): string
    {
        return Template::findOrFail($template_id)->getMedia('templates_order')->first()->getPath();
    }

    public function getExportPath()
    {
        return $this->exportPath = Storage::disk('exports')->path('').'output.docx';
    }

    public function getExportMediaCollection(): string
    {
        return 'export_order';
    }

    public function saveQuietly(array $options = [])
    {
        return static::withoutEvents(function () use ($options) {
            return $this->save($options);
        });
    }
}
