<?php

namespace App\Http\Resources\TaxRate;

use Illuminate\Http\Resources\Json\JsonResource;

class TaxRateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
          'id' => $this->id,
          'tax_rate' => $this->tax_rate,
          'start_date' => $this->start_date,
          'end_date' => $this->end_date ?? null,
          'xero_sales_tax_type' => $this->xero_sales_tax_type ?? null,
          'xero_purchase_tax_type' => $this->xero_purchase_tax_type ?? null,
          'created_at' => $this->created_at ?? null,
          'updated_at' => $this->updated_at ?? null,
        ];
    }
}
