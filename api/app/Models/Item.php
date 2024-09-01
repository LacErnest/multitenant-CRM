<?php

namespace App\Models;

use App\Http\Resources\Item\ItemResource;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class Item extends Model
{
    use uuids;
    use OnTenant;

    protected $fillable = [
        'xero_id', 'entity_id', 'entity_type', 'description', 'quantity', 'unit', 'unit_price',
        'order', 'service_id', 'service_name', 'company_id', 'master_item_id', 'exclude_from_price_modifiers'
    ];

    protected $casts = ['unit_price' => 'float'];

    protected $resourceClass = ItemResource::class;

    public function entity()
    {
        return $this->morphTo();
    }

    public function priceModifiers()
    {
        return $this->morphMany('App\Models\PriceModifier', 'entity');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public static function getResource()
    {
        return (new static)->resourceClass;
    }

    /**
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
