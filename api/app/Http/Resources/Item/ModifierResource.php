<?php

namespace App\Http\Resources\Item;

use App\Enums\CurrencyCode;
use App\Enums\PriceModifierQuantityType;
use App\Enums\PriceModifierType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class ModifierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $companyCurrency = Company::find(getTenantWithConnection())->currency_code;
        $entity = $this->entity instanceof Item ? $this->entity->entity : $this->entity;
        $rate = $entity->currency_rate_company;

        if (PriceModifierQuantityType::isFixed($this->quantity_type)) {
            if (!$entity->manual_input && !$entity instanceof PurchaseOrder && UserRole::isAdmin(auth()->user()->role) && $companyCurrency == CurrencyCode::USD()->getIndex()) {
                $quantity = round($this->quantity * $rate, 2);
            } else {
                $quantity = round($this->quantity, 2);
            }
        } else {
            $quantity = round($this->quantity, 0);
        }

        return [
          'id' => $this->id,
          'xero_id' => $this->xero_id ?? null,
          'entity_id' => $this->entity_id ?? null,
          'entity_type' => $this->entity_type ?? null,
          'type' => $this->type ?? null,
          'description' => $this->description ?? null,
          'quantity' => $quantity,
          'quantity_type' => $this->quantity_type ?? null,
          'created_at' => $this->created_at,
          'updated_at' => $this->updated_at ?? null,
        ];
    }
}
