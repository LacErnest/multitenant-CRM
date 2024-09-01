<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalEntityNotificationSetting extends Model
{
    protected $connection = 'mysql';

    protected $primaryKey = 'legal_entity_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'legal_entity_id',
        'enable_submited_invoice_notification',
        'notification_footer',
        'notification_contacts'
    ];

    protected $casts = [
        'notification_contacts' => 'array',
    ];

    /**
     * @return BelongsTo
     */
    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class, 'legal_entity_id');
    }
}
