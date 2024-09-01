<?php

namespace App\Models;

use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\Http\Resources\Employee\EmployeeResource;
use App\Services\Export\Interfaces\CanBeExported;
use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\InteractsWithMedia;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

/**
 * Class Employee
 *
 * @property string $id
 * @property string $xero_id
 * @property int    $type
 * @property int    $status
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $salary
 * @property int    $working_hours
 * @property string $phone_number
 * @property string $address_id
 * @property string $linked_in_profile
 * @property string $facebook_profile
 * @property Carbon $started_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Employee extends Model implements CanBeExported
{
    use Uuids,
        OnTenant,
        AutoElastic,
        InteractsWithMedia;

    protected $resourceClass = EmployeeResource::class;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'type',
        'status',
        'salary',
        'working_hours',
        'phone_number',
        'started_at',
        'linked_in_profile',
        'facebook_profile',
        'role',
        'default_currency',
        'can_be_borrowed',
        'legal_entity_id',
        'is_pm',
        'overhead_employee',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'started_at',
    ];

    protected $casts = [
        'salary'     => 'float',
    ];

    protected $appends = [
        'name',
    ];

    protected $indexSettings = [
        'analysis' => [
            'normalizer' => [
                'to_lowercase' => [
                    'type'   => 'custom',
                    'filter' => [
                        'lowercase',
                    ],
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
        'first_name' => [
            'type'   => 'text',
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
        'role' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type' => 'keyword',
                    'ignore_above' => 256,
                    'normalizer' => 'to_lowercase'
                ],
            ],
        ],
        'salary' => [
            'type' => 'float',
        ],
        'working_hours' => [
            'type' => 'float',
        ],
        'default_currency' => [
            'type' => 'keyword',
        ],
        'started_at' => [
            'type'   => 'date',
            'format' => 'epoch_second',
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
        'facebook_profile' => [
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
        'address_id' => [
            'type' => 'keyword',
        ],
        'addressline_1' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'addressline_2' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'city' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'region' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'postal_code' => [
            'type' => 'text',
            'fields' => [
                'keyword' => [
                    'type'         => 'keyword',
                    'ignore_above' => 256,
                    'normalizer'   => 'to_lowercase',
                ],
            ],
        ],
        'country' => [
            'type' => 'keyword',
        ],
        'created_at' => [
            'type'   => 'date',
            'format' => 'epoch_second',
        ],
        'updated_at' => [
            'type'   => 'date',
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
        'is_pm' => [
            'type' => 'boolean',
        ],
        'overhead_employee' => [
            'type' => 'boolean',
        ],
    ];

    /**
     * @return string
     */
    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * @return mixed
     */
    public function getTemplatePath($template_id)
    {
        $legalEntitySettingsRepository = App::make(LegalEntitySettingRepositoryInterface::class);
        $legalSetting = $legalEntitySettingsRepository->firstById($this->legalEntity->legal_entity_setting_id);

        $result['employee'] = $legalSetting
            ->getMedia('templates_employee')
            ->first() ?
            $legalSetting->getMedia('templates_employee')->first()->getPath() :
            null;
        $result['contractor'] = $legalSetting
            ->getMedia('templates_contractor')
            ->first() ?
            $legalSetting->getMedia('templates_contractor')->first()->getPath() :
            null;

        return $result;
    }

    /**
     * @return string
     */
    public function getExportPath(): string
    {
        return Storage::disk('exports')->path('') . 'output.docx';
    }

    /**
     * @return string
     */
    public function getExportMediaCollection(): string
    {
        return 'export_employee';
    }

    /**
     * @return string
     */
    public static function getResource()
    {
        return (new static)->resourceClass;
    }

    /** RELATIONS */
    /**
     * @return BelongsTo
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * @return BelongsToMany
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_employees')
            ->withTimestamps()
            ->withPivot('hours', 'employee_cost', 'currency_rate_dollar', 'currency_rate_employee', 'month');
    }

    /**
     * @return HasMany
     */
    public function projectManagers(): HasMany
    {
        return $this->hasMany(Project::class, 'project_manager_id');
    }

    /**
     * @return HasMany
     */
    public function histories(): HasMany
    {
        return $this->hasMany(EmployeeHistory::class, 'employee_id');
    }

    /**
     * @return BelongsTo
     */
    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class, 'legal_entity_id');
    }

    /**
     * @return HasMany
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'resource_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'resource_id');
    }

    public function registerMediaCollections() : void
    {
        $this->addMediaCollection('cv')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword',
            ])
            ->useDisk('employees.cv');
        $this->addMediaCollection('contract')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword',
            ])
            ->useDisk('employees.contract')
            ->singleFile();
    }
}
