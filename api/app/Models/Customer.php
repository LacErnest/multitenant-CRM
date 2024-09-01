<?php

namespace App\Models;

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\Services\Export\Interfaces\CanBeExported;
use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\InteractsWithMedia;

class Customer extends Model implements CanBeExported
{
    use uuids;
    use AutoElastic;
    use InteractsWithMedia;

    protected $connection = 'mysql';

    protected $fillable = [
        'name', 'email', 'status', 'tax_number', 'default_currency', 'website',
        'phone_number', 'sales_person_id', 'description', 'industry', 'company_id', 'billing_address_id', 'operational_address_id',
        'average_collection_period', 'primary_contact_id', 'legacy_customer', 'intra_company', 'non_vat_liable','payment_due_date'
    ];

    protected $dates = ['created_at', 'updated_at'];

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
        'status' => [
            'type' => 'keyword',
        ],
        'company_id' => [
            'type' => 'keyword',
        ],
        'company' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
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
        'description' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'industry' => [
            'type' => 'keyword',
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
            'type' => 'keyword',
        ],
        'website' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
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
        'default_currency' => [
            'type' => 'keyword',
        ],
        'sales_person_id' => [
            'type' => 'keyword',
        ],
        'sales_person' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'primary_contact_id' => [
            'type' => 'keyword',
        ],
        'primary_contact' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'average_collection_period' => [
            'type' => 'integer'
        ],
        'legacy_customer' => [
            'type' => 'boolean'
        ],
        'intra_company' => [
            'type' => 'boolean'
        ],
        'billing_address_id' => [
            'type' => 'keyword',
        ],
        'billing_addressline_1' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256
                ],
            ],
        ],
        'billing_addressline_2' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256
                ],
            ],
        ],
        'billing_city' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256
                ],
            ],
        ],
        'billing_region' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256
                ],
            ],
        ],
        'billing_postal_code' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256
                ],
            ],
        ],
        'billing_country' => [
            'type' => 'keyword',
        ],

        'operational_address_id' => [
            'type' => 'keyword',
        ],
        'operational_addressline_1' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256
                ],
            ],
        ],
        'operational_addressline_2' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256
                ],
            ],
        ],
        'operational_city' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256
                ],
            ],
        ],
        'operational_region' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256
                ],
            ],
        ],
        'operational_postal_code' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256
                ],
            ],
        ],
        'operational_country' => [
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
        'contacts' => [
            'type' => 'object',
        ],
    ];

    /** RELATIONS */

    /**
     * @return BelongsToMany
     */
    public function leadGenerationSales(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_sales', 'customer_id', 'sales_person_id')
            ->withPivot('id', 'project_id', 'pay_date', 'invoice_id');
    }

    /**
     * @return BelongsToMany
     */
    public function legacyCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'legacy_customers')
            ->withTimestamps()->withPivot('id');
    }

    /**
     * @return MorphMany
     */
    public function xeroEntities(): MorphMany
    {
        return $this->morphMany(XeroEntityStorage::class, 'document');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company');
    }
    public function billing_address()
    {
        return $this->belongsTo('App\Models\CustomerAddress', 'billing_address_id');
    }

    public function operational_address()
    {
        return $this->belongsTo('App\Models\CustomerAddress', 'operational_address_id');
    }

    public function contacts()
    {
        return $this->hasMany('App\Models\Contact');
    }

    public function primaryContact()
    {
        return $this->hasOne('App\Models\Contact', 'id', 'primary_contact_id');
    }

    public function salesPerson()
    {
        return $this->belongsTo('App\Models\User', 'sales_person_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('export_customer_nda')->useDisk('exports')->singleFile();
    }

    public function getTemplatePath($template_id): string
    {
        return Setting::first()->getMedia('templates_NDA')->first()->getPath();
    }

    public function getCustomerTemplatePath(string $legalEntityId): string
    {
        $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
        $legalEntitySettingsRepository = App::make(LegalEntitySettingRepositoryInterface::class);
        $legalEntity = $legalEntityRepository->firstById($legalEntityId);
        $legalSetting = $legalEntitySettingsRepository->firstById($legalEntity->legal_entity_setting_id);

        return $legalSetting->getMedia('templates_customer')->first()->getPath();
    }

    public function getExportPath()
    {
        return $this->exportPath = Storage::disk('exports')->path('') . 'output.docx';
    }

    public function getExportMediaCollection(): string
    {
        return 'export_customer_nda';
    }
}
