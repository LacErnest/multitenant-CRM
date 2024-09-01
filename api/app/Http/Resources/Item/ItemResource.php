<?php

namespace App\Http\Resources\Item;

use App\Enums\CurrencyCode;
use App\Enums\PriceModifierQuantityType;
use App\Enums\PriceModifierType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $isExternalAuth = auth()->user() instanceof Resource;
        $company = Company::find($this->company_id);
        $companyCurrency = Company::find(getTenantWithConnection())->currency_code;

        return [
          'id' => $this->id,
          'xero_id' => $this->xero_id ?? null,
          'entity_id' => $this->entity_id ?? null,
          'entity_type' => $this->entity_type ?? null,
          'service_id' => $this->service_id ?? null,
          'service_name' => $this->service_name ?? null,
          'description' => $this->description ?? null,
          'quantity' => $this->quantity ?? null,
          'unit' => $this->unit ?? null,
          'unit_price' => $isExternalAuth ? round($this->unit_price, 2) :
              (auth()->user() ? $this->entity instanceof PurchaseOrder || $this->entity->manual_input ? round($this->unit_price, 2) :
                  (UserRole::isAdmin(auth()->user()->role) && $companyCurrency == CurrencyCode::USD()->getIndex()
                      ? round($this->unit_price * $this->entity->currency_rate_company, 2) :
                      round($this->unit_price, 2)) : round($this->unit_price, 2)),
          'order' => $this->order ?? null,
          'created_at' => $this->created_at,
          'updated_at' => $this->updated_at ?? null,
          'price_modifiers' => ModifierResource::collection($this->priceModifiers->load('entity')->sortBy('quantity_type')),
          'company_id' => $this->company_id ?? null,
          'company_name' => $company->name ?? null,
          'exclude_from_price_modifiers'=>$this->exclude_from_price_modifiers ?? true,
        ];
    }
}
