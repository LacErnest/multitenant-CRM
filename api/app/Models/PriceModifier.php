<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class PriceModifier extends Model
{
    use uuids;
    use OnTenant;

    protected $fillable = ['xero_id', 'entity_id', 'entity_type', 'description', 'quantity', 'type', 'quantity_type'];

    public function entity()
    {
        return $this->morphTo();
    }
}
