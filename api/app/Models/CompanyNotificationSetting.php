<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyNotificationSetting extends Model
{
    protected $connection = 'mysql';

    protected $primaryKey = 'company_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'company_id',
        'from_address',
        'from_name',
        'invoice_submitted_body',
        'cc_addresses'
    ];

    protected $casts = [
        'cc_addresses' => 'array',
    ];

    /**
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
