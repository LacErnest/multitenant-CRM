<?php

namespace App\Models;

use App\Enums\CommissionModel;
use App\Http\Resources\User\UserResource;
use App\Notifications\Auth\PasswordResetNotification;
use App\Notifications\Auth\PasswordSetNotification;
use App\Traits\Models\AutoElastic;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Hash;
use Tenancy\Facades\Tenancy;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use uuids;
    use AutoElastic;

    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'company_id',
        'role',
        'disabled_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $appends = [
        'name'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'disabled_at',
    ];

    protected $resourceClass = UserResource::class;

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
        'role' => [
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
        'disabled_at' => [
            'type' => 'date',
            'format' => 'epoch_second',
        ],
    ];

    protected bool $hashPassword = true;

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PasswordResetNotification($token));
    }

    public function sendPasswordSetNotification($token)
    {
        $this->notify(new PasswordSetNotification($token));
    }

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function customer()
    {
        return $this->hasMany('App\Models\Customer', 'sales_person_id');
    }

    public function saleProjects(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Project', getTenantConnectionName().'.'.'project_sales_persons')->withTimestamps()->withPivot('id');
    }

    public function leadGenProjects(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Project', getTenantConnectionName().'.'.'project_lead_gens')->withTimestamps()->withPivot('id');
    }

    public function quotes(): BelongsToMany
    {
        return $this->belongsToMany(Quote::class, getTenantConnectionName().'.'.'quote_sales_persons')->withTimestamps()->withPivot('id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany('App\Models\Comment', 'created_by');
    }

    public function salesCommissions()
    {
        return $this->hasMany('App\Models\SalesCommission', 'sales_person_id');
//            ->withDefault(['sales_person_id' => $this->id,
//                'commission' => SalesCommission::DEFAULT_COMMISSION,
//                'commission_model' => CommissionModel::default()->getIndex()]);
    }

    public function customerSales(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_sales', 'sales_person_id')
            ->withPivot('id', 'project_id', 'pay_date', 'invoice_id');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function disablePasswordHashing()
    {
        $this->hashPassword = false;
    }

    public function setPasswordAttribute($password)
    {
        if (!empty($password) && $this->hashPassword) {
            $this->attributes['password'] = Hash::make($password);
        } elseif (!empty($password)) {
            $this->attributes['password'] = $password;
        }
    }

    public function table_preferences()
    {
        return $this->hasMany(TablePreference::class, 'user_id');
    }

    public function mail_preference()
    {
        return $this->hasOne(MailPreference::class, 'user_id');
    }

    public static function getResource()
    {
        return (new static)->resourceClass;
    }
}
