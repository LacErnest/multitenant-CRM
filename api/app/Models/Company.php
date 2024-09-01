<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Symfony\Component\Console\Input\InputInterface;
use Tenancy\Identification\Contracts\Tenant;
use Tenancy\Identification\Drivers\Console\Contracts\IdentifiesByConsole;
use Tenancy\Identification\Drivers\Http\Contracts\IdentifiesByHttp;
use Tenancy\Identification\Drivers\Queue\Contracts\IdentifiesByQueue;
use Tenancy\Identification\Drivers\Queue\Events\Processing;
use Tenancy\Tenant\Events\Created;
use Tenancy\Tenant\Events\Updated;
use Tenancy\Tenant\Events\Deleted;
use Illuminate\Http\Request;

/**
 * Class Company
 *
 * @property string $id
 * @property string $name
 * @property int    $currency_code
 * @property Carbon $acquisition_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read User[]     $users
 * @property-read Contact[]  $contacts
 * @property-read Customer[] $customers
 */
class Company extends Model implements
    Tenant,
    IdentifiesByHttp,
    IdentifiesByConsole,
    IdentifiesByQueue
{
    use Uuids;

    public $incrementing = false;

    protected $connection = 'mysql';

    protected $dispatchesEvents = [
        'created' => Created::class,
        'updated' => Updated::class,
        'deleted' => Deleted::class,
    ];

    protected $fillable = [
        'name',
        'currency_code',
        'acquisition_date',
        'earnout_years',
        'earnout_bonus',
        'gm_bonus',
        'sales_support_commission',
    ];

    protected $keyType = 'string';

    /**
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'company_id');
    }
    /**
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany('App\Models\User', 'company_id');
    }

    /**
     * @return HasMany
     */
    public function increments(): HasMany
    {
        return $this->hasMany('App\Models\AutoIncrement', 'company_id');
    }

    /**
     * @return HasMany
     */
    public function contacts(): HasMany
    {
        return $this->hasMany('App\Models\Contact');
    }

    /**
     * @return HasMany
     */
    public function customers(): HasMany
    {
        return $this->hasMany('App\Models\Customer');
    }

    /**
     * @return HasOne
     */
    public function setting(): HasOne
    {
        return $this->hasOne('App\Models\CompanySetting');
    }

    /**
     * @return BelongsToMany
     */
    public function legalEntities(): BelongsToMany
    {
        return $this->belongsToMany(LegalEntity::class, 'company_legal_entity')
            ->withTimestamps()->withPivot('id', 'default', 'local');
    }

    /**
     * @return BelongsToMany
     */
    public function legacyCustomers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'legacy_customers')
            ->withTimestamps()->withPivot('id');
    }

    /**
     * @return LegalEntity
     */
    public function defaultLegalEntity(): LegalEntity
    {
        return $this->legalEntities()->wherePivot('default', true)->first();
    }

    /**
     * The attribute of the Model to use for the key.
     *
     * @return string
     */
    public function getTenantKeyName(): string
    {
        return 'id';
    }

    /**
     * The actual value of the key for the tenant Model.
     *
     * @return string|int
     */
    public function getTenantKey()
    {
        return str_replace('-', null, $this->id);
    }

    /**
     * A unique identifier, eg class or table to distinguish this tenant Model.
     *
     * @return string
     */
    public function getTenantIdentifier(): string
    {
        return get_class($this);
    }

    /**
     * Specify whether the tenant model is matching the request.
     *
     * @param  Request $request // todo shouldn't be used here, refactor later
     *
     * @return Tenant
     */
    public function tenantIdentificationByHttp(Request $request): ?Tenant
    {
        if (app()->runningInConsole()) {
            return null;
        }

        $company_id = explode('/', $request->path())[1];

        return $this->query()
            ->where('id', $company_id)
            ->first();
    }

    /**
     * @param  InputInterface $input
     *
     * @return Tenant|null
     */
    public function tenantIdentificationByConsole(InputInterface $input): ?Tenant
    {
        if ($input->hasArgument('tenant') && $company_id = $input->getArgument('tenant')) {
            return $this->newQuery()
              ->where('id', $company_id)
              ->first();
        }

        return null;
    }

    /**
     * @param  Processing $event
     *
     * @return Tenant|null
     */
    public function tenantIdentificationByQueue(Processing $event): ?Tenant
    {
        if ($event->tenant) {
            return $event->tenant;
        }

        if ($event->tenant_key && $event->tenant_identifier === $this->getTenantIdentifier()) {
            return $this->newQuery()
              ->where($this->getTenantKeyName(), $event->tenant_key)
              ->first();
        }

        return null;
    }

    /**
     * @return string
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        $initials = '';

        foreach ($words as $w) {
            $initials .= $w[0];
        }

        return $initials;
    }


    /**
     * @return HasOne
     */
    public function notificationSetting(): HasOne
    {
        return $this->hasOne(CompanyNotificationSetting::class, 'company_id');
    }
}
