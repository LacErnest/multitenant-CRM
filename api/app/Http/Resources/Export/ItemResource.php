<?php

namespace App\Http\Resources\Export;

use App\Enums\CurrencyCode;
use App\Models\PurchaseOrder;

class ItemResource extends ExtendedJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $convertedPrice = round($this->unit_price * $this->entity->currency_rate_customer, 2);
        $symbol = CurrencyCode::make($this->entity->currency_code)->getValue();
        return [
          'VAR_I_DESCRIPTION'  => $this->service_name && $this->description ?
              $this->service_name . ' (' . $this->description . ')' :
              $this->service_name,
          'VAR_I_QUANTITY'     => $this->quantity,
          'VAR_I_PRICE'        => $this->entity->manual_input || $this->entity instanceof PurchaseOrder ?
              $this->currencyFormatter->formatCurrency($this->unit_price, $symbol) : $this->currencyFormatter->formatCurrency($convertedPrice, $symbol),
          'VAR_I_TOTAL_PRICE'  => $this->entity->manual_input || $this->entity instanceof PurchaseOrder ?
              $this->currencyFormatter->formatCurrency($this->unit_price * $this->quantity, $symbol) :
              $this->currencyFormatter->formatCurrency($convertedPrice * $this->quantity, $symbol),
          'VAR_I_UNIT'         => $this->unit ?? null,
          'VAR_price_modifier' => $this->priceModifiers ?
              ItemModifierResource::collection($this->priceModifiers->sortBy('created_at'))->resolve() :
              [],
        ];
    }
}
