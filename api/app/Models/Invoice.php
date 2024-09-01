<?php

/** @noinspection PhpSuperClassIncompatibleWithInterfaceInspection */

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Services\Export\Interfaces\CanBeExported;
use App\Traits\Models\AutoElastic;
use App\Traits\Models\HasComments;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Events\CollectionHasBeenCleared;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class Invoice extends Model implements CanBeExported
{
    use uuids;
    use OnTenant;
    use AutoElastic;
    use InteractsWithMedia;
    use HasComments;

    protected $fillable = [
        'xero_id',
        'created_by',
        'project_id',
        'order_id',
        'type',
        'date',
        'pay_date',
        'due_date',
        'close_date',
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
        'purchase_order_id',
        'manual_input',
        'manual_price',
        'manual_vat',
        'down_payment',
        'down_payment_status',
        'legal_entity_id',
        'master',
        'shadow',
        'vat_status',
        'vat_percentage',
        'total_paid',
        'payment_terms',
        'submitted_date',
        'eligible_for_earnout',
        'customer_notified_at',
        'email_template_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'date',
        'due_date',
        'pay_date',
        'close_date',
        'details.rows.date',
        'payment_terms',
        'customer_notified_at'
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
        'vat_percentage' => 'float',
        'master' => 'boolean',
        'shadow' => 'boolean',
        'eligible_for_earnout' => 'boolean',
    ];

    protected $resourceClass = InvoiceResource::class;

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
        'created_by' => [
            'type' => 'keyword'
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
        'quote_status' => [
            'type' => 'keyword',
        ],
        'quote_date' => [
            'type' => 'date',
            'format' => 'epoch_second',
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
        'order_status' => [
            'type' => 'keyword',
        ],
        'order_paid_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'is_last_paid_invoice' => [
            'type' => 'boolean',
        ],
        'customer_id' => [
            'type' => 'keyword',
        ],
        'customer_notified_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
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
        'type' => [
            'type' => 'keyword',
        ],
        'date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'due_date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'pay_date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'close_date' => [
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
        'customer_currency_code' => [
            'type' => 'keyword',
        ],
        'total_price' => [
            'type' => 'float',
        ],
        'total_paid' => [
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
        'total_paid_amount_usd' => [
            'type' => 'float',
        ],
        'total_paid_amount' => [
            'type' => 'float',
        ],
        'customer_total_price' => [
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
        'details' => [
            'type' => 'object',
        ],
        'resource_id' => [
            'type' => 'keyword'
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
        'purchase_order_id' => [
            'type' => 'keyword'
        ],
        'purchase_order' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'credit_notes_total_price' => [
            'type' => 'float',
        ],
        'credit_notes_total_price_usd' => [
            'type' => 'float',
        ],
        'credit_notes_total_vat' => [
            'type' => 'float',
        ],
        'credit_notes_total_vat_usd' => [
            'type' => 'float',
        ],
        'download' => [
            'type' => 'boolean'
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
        'sales_person_id' => [
            'type' => 'keyword',
        ],
        'master' => [
            'type' => 'boolean',
        ], 'shadow' => [
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
        'all_invoices_paid' => [
            'type' => 'boolean',
        ],
        'second_sales_person_id' => [
            'type' => 'keyword',
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
     * @return HasMany
     */
    public function masterInvoice(): HasOne
    {
        return $this->hasOne(MasterShadow::class, 'shadow_id');
    }

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

    public function creator()
    {
        return $this->hasOne('App\Models\User', 'id', 'created_by');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo('App\Models\PurchaseOrder');
    }

    public function creditNotes()
    {
        return $this->hasMany('App\Models\CreditNote');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\InvoicePayment');
    }

    public function order()
    {
        return $this->belongsTo('App\Models\Order');
    }

    public function items()
    {
        return $this->morphMany('App\Models\Item', 'entity');
    }

    public function emailTemplate()
    {
        return $this->belongsTo(EmailTemplate::class);
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
        $this->addMediaCollection('invoice_uploads')->useDisk('invoices')->singleFile();
        $this->addMediaCollection('export_invoice')->useDisk('exports')->singleFile();
    }

    public function getTemplatePath($template_id): string
    {
        return Template::findOrFail($template_id)->getMedia('templates_invoice')->first()->getPath();
    }

    public function getExportPath()
    {
        return $this->exportPath = Storage::disk('exports')->path('') . 'output.docx';
    }

    public function getExportMediaCollection(): string
    {
        return 'export_invoice';
    }

    public function saveQuietly(array $options = [])
    {
        return static::withoutEvents(function () use ($options) {
            return $this->save($options);
        });
    }

    /**
     * Scope a query to only include paid invoices.
     */
    public function scopePaidWithoutPartialPayment(Builder $query): Builder
    {
        return $query->having('total_price', '>', $this->payments()->sum('pay_amount'))
            ->where('status', InvoiceStatus::paid()->getIndex());
    }

    /**
     * Scope a query to only include paid invoices.
     */
    public function scopePaidOrPartiallyPaid(Builder $query): Builder
    {
        $totalPaid = $this->payments->sum('pay_amount');
        return $query->where(function ($subQuery) use ($totalPaid) {
            $subQuery->where('total_paid', '>', $totalPaid)
                ->orWhere('status', InvoiceStatus::paid()->getIndex());
        });
    }

    /**
     * Scope a query to only include master invoices.
     */
    public function scopeMaster(Builder $query): Builder
    {
        return $query->where('master', true);
    }
}
