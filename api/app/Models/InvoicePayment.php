<?php

namespace App\Models;

use App\Http\Resources\Payment\InvoicePaymentResource;
use App\Services\Export\Interfaces\CanBeExported;
use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\InteractsWithMedia;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class InvoicePayment extends Model
{
    use uuids;
    use OnTenant;
    use AutoElastic;

    protected $fillable = [
        'created_by',
        'invoice_id',
        'pay_date',
        'number',
        'pay_amount',
        'currency_code'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'pay_date',
    ];

    protected $casts = [
        'pay_amount' => 'float'
    ];

    protected $resourceClass = InvoicePaymentResource::class;

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
        'invoice_info' => [
            'type' => 'nested',
            'properties' => [
                'id' => [
                    'type' => 'keyword'
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
                'status' => [
                    'type' => 'keyword',
                ],
                'currency_code' => [
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
            ]
        ],
        'invoice_id' => [
            'type' => 'keyword',
        ],
        'invoice' => [
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
        'pay_date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'currency_code' => [
            'type' => 'keyword',
        ],
        'pay_amount' => [
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
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo('App\Models\Invoice');
    }

    public function creator(): HasOne
    {
        return $this->hasOne('App\Models\User', 'id', 'created_by');
    }

    public static function getResource()
    {
        return (new static)->resourceClass;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('export_invoice_payment')->useDisk('exports')->singleFile();
    }

    public function getTemplatePath($template_id): string
    {
        return Template::findOrFail($template_id)->getMedia('templates_invoice_payment')->first()->getPath();
    }

    public function saveQuietly(array $options = [])
    {
        return static::withoutEvents(function () use ($options) {
            return $this->save($options);
        });
    }
}
