<?php

namespace App\Http\Resources\LegalEntity;

use Illuminate\Http\Resources\Json\JsonResource;

class LegalEntitySettingsResource extends JsonResource
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
          'id'                                => $this->id,
          'quote_number'                      => $this->quote_number,
          'quote_number_format'               => $this->quote_number_format,
          'order_number'                      => $this->order_number,
          'order_number_format'               => $this->order_number_format,
          'invoice_number'                    => $this->invoice_number,
          'invoice_number_format'             => $this->invoice_number_format,
          'purchase_order_number'             => $this->purchase_order_number,
          'purchase_order_number_format'      => $this->purchase_order_number_format,
          'resource_invoice_number'           => $this->resource_invoice_number,
          'resource_invoice_number_format'    => $this->resource_invoice_number_format,
          'created_at'                        => $this->created_at,
          'updated_at'                        => $this->updated_at,
        ];
    }
}
