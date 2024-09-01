<?php

namespace App\Models;

use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\Enums\TablePreferenceType;
use App\Services\Export\Interfaces\CanBeExported;
use App\Traits\Models\HasCustomAutoIncrement;
use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\InteractsWithMedia;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class Resource extends Model implements CanBeExported
{
    use Uuids,
        OnTenant,
        AutoElastic,
        InteractsWithMedia,
        Authenticatable,
        Authorizable,
        HasCustomAutoIncrement;

    /**
     * @var string
     */
    protected $autoEntity = 'resources';
    /**
     * @var string
     */
    protected $autoAttribute = 'number';

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'type',
        'status',
        'tax_number',
        'default_currency',
        'phone_number',
        'hourly_rate',
        'daily_rate',
        'average_rating',
        'job_title',
        'can_be_borrowed',
        'legal_entity_id',
        'non_vat_liable',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'hourly_rate' => 'float',
        'daily_rate' => 'float',
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
        'type' => [
            'type' => 'keyword',
        ],
        'status' => [
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
        'first_name' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'last_name' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'email' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'tax_number' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'default_currency' => [
            'type' => 'keyword',
        ],
        'hourly_rate' => [
            'type' => 'float',
        ],
        'daily_rate' => [
            'type' => 'float',
        ],
        'phone_number' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'address_id' => [
            'type' => 'keyword',
        ],
        'addressline_1' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'addressline_2' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'city' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'region' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'postal_code' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'country' => [
            'type' => 'keyword',
        ],
        'job_title' => [
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
        'company_id' => [
            'type' => 'keyword',
        ],
        'can_be_borrowed' => [
            'type' => 'boolean',
        ],
        'non_vat_liable' => [
            'type' => 'boolean',
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

    /**
     * @return MorphMany
     */
    public function xeroEntities(): MorphMany
    {
        return $this->morphMany(XeroEntityStorage::class, 'document');
    }

    public function purchaseOrders()
    {
        return $this->hasMany('App\Models\PurchaseOrder');
    }

    public function address()
    {
        return $this->belongsTo('App\Models\Address');
    }

    public function resourceLogin()
    {
        return $this->hasOne('App\Models\ResourceLogin', 'resource_id');
    }

    public function table_preferences()
    {
        return $this->hasMany(TablePreference::class, 'user_id');
    }

    public function getTemplatePath($template_id)
    {
        $legalEntitySettingsRepository = App::make(LegalEntitySettingRepositoryInterface::class);
        $legalSetting = $legalEntitySettingsRepository->firstById($this->legalEntity->legal_entity_setting_id);

        $result['nda'] = $legalSetting->getMedia('templates_NDA')->first() ? $legalSetting->getMedia('templates_NDA')->first()->getPath() : null;
        $result['contractor'] = $legalSetting->getMedia('templates_contractor')->first() ? $legalSetting->getMedia('templates_contractor')->first()->getPath() : null;
        $result['freelancer'] = $legalSetting->getMedia('templates_freelancer')->first() ? $legalSetting->getMedia('templates_freelancer')->first()->getPath() : null;
        return $result;
    }

    public function getExportPath()
    {
        return Storage::disk('exports')->path('').'output.docx';
    }

    public function getExportMediaCollection(): string
    {
        return 'export_resource';
    }

    public function services(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany('App\Models\Service', 'resource_id');
    }

    public function registerMediaCollections() : void
    {
        $this->addMediaCollection('contract')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword',
            ])
            ->useDisk('resources.contract')
            ->singleFile();
    }
}
