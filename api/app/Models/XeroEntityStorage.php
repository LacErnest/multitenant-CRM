<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class XeroLinkStorage
 *
 * @property string $id
 * @property string $xero_id
 * @property string $legal_entity_id
 * @property string $document_id
 * @property string $document_type
 */
class XeroEntityStorage extends Model
{
    use Uuids;

    protected $connection = 'mysql';

    protected $fillable = [
        'xero_id',
        'legal_entity_id',
        'document_id',
        'document_type',
    ];

    /** RELATIONS */
    /**
     * @return MorphTo
     */
    public function document(): MorphTo
    {
        return $this->morphTo();
    }
}
