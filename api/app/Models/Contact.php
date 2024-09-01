<?php

namespace App\Models;

use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Carbon\Exceptions\Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;

/**
 * Class Contact
 *
 * @property string    $id
 * @property string    $xero_tenant_id
 * @property string    $xero_access_token
 * @property string    $xero_refresh_token
 * @property string    $name
 * @property integer   $currency_code
 * @property string    $acquisition_date
 * @property Exception $created_at
 * @property Exception $updated_at
 */
class Contact extends Model
{
    use Uuids, AutoElastic, Notifiable;

    protected $connection = 'mysql';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'department',
        'title',
        'gender',
        'linked_in_profile',
    ];

    protected $touches = [
        'customer',
    ];

    protected $indexSettings = [
        'analysis' => [
            'normalizer' => [
                'to_lowercase' => [
                    'type' => 'custom',
                    'filter' => [
                        'lowercase',
                    ],
                ],
            ],
        ],
    ];

    protected $mappingProperties = [
        'id' => [
            'type' => 'keyword',
        ],
        'first_name' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'last_name' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'name' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'email' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'phone_number' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'department' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'title' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'gender' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'linked_in_profile' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'primary_contact' => [
            'type' => 'boolean',
        ],
        'customer_id' => [
            'type' => 'keyword',
        ],
        'customer' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'created_at' => [
            'type'   => 'date',
            'format' => 'epoch_second',
        ],
        'updated_at' => [
            'type'   => 'date',
            'format' => 'epoch_second',
        ],
    ];

    protected $appends = [
        'name',
    ];

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**RELATIONS */
    /**
     * @return HasMany
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'contact_id');
    }

    /**
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasManyThrough
     */
    public function quotes(): HasManyThrough
    {
        return $this->hasManyThrough(
            Quote::class,
            Project::class
        );
    }

    /**
     * @return MorphMany
     */
    public function xeroEntities(): MorphMany
    {
        return $this->morphMany(XeroEntityStorage::class, 'document');
    }
}
