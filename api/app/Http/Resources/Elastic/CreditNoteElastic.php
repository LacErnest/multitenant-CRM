<?php

namespace App\Http\Resources\Elastic;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteElastic extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
          'id' => $this->id,
          'date' => $this->date->timestamp ?? null,
          'status' => $this->status ?? null,
          'type' => $this->type ?? null,
          'number' => $this->number ?? null,
          'total_price' => $this->total_price,
          'total_vat' => $this->total_vat,
          'total_price_usd' => $this->total_price_usd,
          'total_vat_usd' => $this->total_vat_usd,
        ];
    }
}
