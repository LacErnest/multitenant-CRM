<?php

namespace App\Models;

use App\Http\Resources\Service\ServiceResource;
use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class Service extends Model
{
    use uuids;
    use OnTenant;
    use AutoElastic;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'price_unit',
        'resource_id',
    ];

    protected $resourceClass = ServiceResource::class;

    protected $casts = [
        'price' => 'float',
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
        'name' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'price' => [
            'type' => 'float',
        ],
        'price_unit' => [
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
        'company_id' => [
            'type' => 'keyword',
        ],
        'created_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'updated_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'deleted_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
    ];

    public function items()
    {
        return $this->hasMany(Item::class, 'service_id');
    }

    public static function getResource()
    {
        return (new static)->resourceClass;
    }

    public function resourceOfService(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo('App\Models\Resource', 'resource_id');
    }

    public function employeeOfService() : BelongsTo
    {
        return $this->belongsTo(Employee::class, 'resource_id');
    }
}
