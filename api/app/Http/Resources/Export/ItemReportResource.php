<?php

namespace App\Http\Resources\Export;

use App\Enums\CurrencyCode;
use App\Models\Company;
use App\Models\PurchaseOrder;

class ItemReportResource extends ExtendedJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $manualInput = $this->entity->manual_input || $this->entity instanceof PurchaseOrder;
        $companyCurrency = Company::find(getTenantWithConnection())->currency_code;
        $convertedPrice = $this->unit_price;
        if (!$manualInput) {
            $symbol = CurrencyCode::make($companyCurrency)->getValue();
        } else {
            $symbol = CurrencyCode::make($this->entity->currency_code)->getValue();
        }

        return [
          'VAR_I_DESCRIPTION'  => $this->service_name && $this->description ?
              $this->service_name . ' (' . $this->description . ')' :
              $this->service_name,
          'VAR_I_QUANTITY'     => $this->quantity,
          'VAR_I_PRICE'        => $this->currencyFormatter->formatCurrency($convertedPrice, $symbol),
          'VAR_I_TOTAL_PRICE'  => $this->currencyFormatter->formatCurrency($convertedPrice * $this->quantity, $symbol),
          'VAR_I_UNIT'         => $this->unit ?? null,
          'VAR_price_modifier' => $this->priceModifiers ?
              ItemModifierResource::collection($this->priceModifiers->sortBy('created_at'))->resolve() :
              [],
        ];
    }
}
