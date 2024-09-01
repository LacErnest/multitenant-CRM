<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Models\Uuids;
use Illuminate\Notifications\Notifiable;

class Commission extends Model
{
    use Notifiable;
    use uuids;

    const MINIMUM_COMMISSION_PAYMENT_AMOUNT = 0.01;

    protected $connection = 'mysql';

    protected $fillable = [
        'sales_person_id',
        'order_id',
        'invoice_id',
        'paid_value',
        'total'
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
            'type' => 'keyword'
        ],
        'sales_person_id' => [
            'type' => 'keyword'
        ],
        'order_id' => [
            'type' => 'keyword'
        ],
        'paid_value' => [
            'type' => 'float'
        ],
        'created_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'updated_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ]
    ];

    public function salesPerson()
    {
        return $this->belongsTo('App\Models\User', 'sales_person_id');
    }

    public function order()
    {
        return $this->belongsTo('App\Models\Order');
    }

    /**
     * Check if exist some paid value and return
     *
     * @param string $salesPersonId
     * @param string $orderId
     *
     * @return array
    */
    public static function getPaidValue($salesPersonId, $orderId, $invoiceId)
    {
        $array = [];
        $commission = Commission::where('sales_person_id', $salesPersonId)
            ->where('order_id', $orderId)
            ->where('invoice_id', $invoiceId)
            ->first();

        if (!$commission) {
            $array['paid_value'] = 0.0;
            $array['paid_at'] = null;
            return $array;
        }

        if ($commission->paid_value == $commission->total) {
            $array['paid_value'] = $commission->paid_value;
            $array['paid_at'] = $commission->updated_at;
        } else {
            $array['paid_value'] = $commission->paid_value;
            $array['paid_at'] = null;
        }
        return $array;
    }

    /**
     * Check if exist some paid value and return
     *
     * @param float $paidValue
     * @param float $commission
     *
     * @return string
    */
    public static function getStatus($paidValue, $commission)
    {
        if ($commission == $paidValue) {
            return 'paid';
        } elseif ($commission > $paidValue && $paidValue > 0) {
            return 'partial paid';
        } else {
            return 'unpaid';
        }
    }
}
