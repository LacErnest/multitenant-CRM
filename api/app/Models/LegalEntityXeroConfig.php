<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class LegalEntityXeroConfig
 * configure Xero account to legal entity
 *
 * @property string xero_tenant_id
 * @property string xero_access_token
 * @property string xero_refresh_token
 * @property string xero_id_token
 * @property string xero_oauth2_state
 * @property Carbon xero_expires
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class LegalEntityXeroConfig extends Model
{
    use Uuids;

    protected $connection = 'mysql';

    protected $fillable = [
        'xero_tenant_id',
        'xero_access_token',
        'xero_refresh_token',
        'xero_id_token',
        'xero_oauth2_state',
        'xero_expires',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /** RELATIONS */
    /**
     * @return HasMany
     */
    public function legalEntities(): HasMany
    {
        return $this->hasMany(LegalEntity::class, 'legal_entity_id');
    }
}
