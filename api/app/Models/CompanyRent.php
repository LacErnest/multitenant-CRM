<?php

namespace App\Models;

use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class CompanyRent extends Model
{
    use Uuids,
        OnTenant,
        SoftDeletes,
        AutoElastic;

    protected $fillable = [
        'start_date',
        'amount',
        'name',
        'end_date',
        'author_id',
        'admin_amount',
    ];

    protected $casts = [
        'amount' => 'float',
        'admin_amount' => 'float',
    ];

    protected $dates = [
        'start_date',
        'end_date',
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
        'start_date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'end_date' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
        'amount' => [
            'type' => 'float',
        ],
        'admin_amount' => [
            'type' => 'float',
        ],
        'author_id' => [
            'type' => 'keyword',
        ],
        'author' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
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

    /** RELATIONS */
    /**
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
