<?php

namespace App\Models;

use App\Http\Resources\ResourceInvoice\ResourceInvoiceResource;
use App\Services\Export\Interfaces\CanBeExported;
use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class ResourceInvoice extends Model
{
    use uuids;
    use OnTenant;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invoices';

    protected $fillable = ['xero_id', 'created_by', 'project_id', 'order_id', 'type', 'date', 'pay_date', 'due_date', 'status',
        'number', 'reference', 'currency_code', 'total_price', 'total_vat', 'total_price_usd', 'total_vat_usd',
        'currency_rate_company', 'currency_rate_customer', 'purchase_order_id'];

    protected $dates = ['created_at', 'updated_at', 'date', 'due_date', 'pay_date', 'details.rows.date'];

    protected $casts = [ 'total_price' => 'float', 'total_price_usd' => 'float', 'total_vat' => 'float', 'total_vat_usd' => 'float',
        'currency_rate_company' => 'float', 'currency_rate_customer' => 'float'];

    protected $resourceClass = ResourceInvoiceResource::class;

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
                'budget' => [
                    'type' => 'float'
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
        'purchase_order_id' => [
            'type' => 'keyword',
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
        'download' => [
            'type' => 'boolean'
        ],
    ];

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

    public function order()
    {
        return $this->belongsTo('App\Models\Order');
    }

    public function items()
    {
        return $this->morphMany('App\Models\Item', 'entity');
    }

    public function priceModifiers()
    {
        return $this->morphMany('App\Models\PriceModifier', 'entity');
    }

    public static function getResource()
    {
        echo 'gets here';
        return (new static)->resourceClass;
    }
}
