<?php

namespace App\Models;

use App\Traits\Models\AutoElastic;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Models\Uuids;
use Illuminate\Notifications\Notifiable;

class CommissionPaymentLog extends Model
{
    use Notifiable;
    use uuids;
    use AutoElastic;

    const MINIMUM_COMMISSION_PAYMENT_AMOUNT = 0.01;

    protected $connection = 'mysql';

    protected $fillable = ['sales_person_id', 'amount', 'approved', 'payment_date'];

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
        'amount' => [
            'type' => 'float'
        ],
        'approved' => [
            'type' => 'boolean'
        ],
        'payment_date' => [
            'type' => 'date',
            'format' => 'epoch_second',
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
}
