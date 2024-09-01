<?php

namespace App\Http\Resources\Export;

use App\Enums\CurrencyCode;
use App\Enums\PriceModifierQuantityType;
use App\Enums\PriceModifierType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\PurchaseOrder;

class ItemModifierResource extends ExtendedJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->currencyFormatter = new \NumberFormatter('en-US', \NumberFormatter::CURRENCY);
        $type = PriceModifierType::isDiscount($this->type) ? '- ' : '+ ';
        $quantityType = PriceModifierQuantityType::isPercentage($this->quantity_type) ? '%' : '';
        $entity = $this->entity->entity;
        $customerRate = $entity->currency_rate_customer;
        $symbol = CurrencyCode::make($entity->currency_code)->getValue();

        if (PriceModifierQuantityType::isFixed($this->quantity_type)) {
            if ($entity->manual_input || $entity instanceof PurchaseOrder) {
                $quantity = round($this->quantity, 2);
            } else {
                $quantity = round($this->quantity * $customerRate, 2);
            }
            $quantity = $this->currencyFormatter->formatCurrency($quantity, $symbol);
        } else {
            $quantity = round($this->quantity, 0);
        }

        return [
          'VAR_I_M_TYPE'         => $this->type ?? null,
          'VAR_I_M_DESCRIPTION'  => $this->description ?? null,
          'VAR_I_M_QUANTITY'     => PriceModifierQuantityType::isFixed($this->quantity_type) ?
              $type . $quantity :
              $type . $quantity . $quantityType,
          'VAR_I_M_QUANTITY_TYPE' => $this->quantity_type ?? null,
        ];
    }
}
