<?php

namespace App\Http\Resources\Payment;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoicePaymentResource extends JsonResource
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
          'id'                     => $this->id,
          'created_by'             => $this->created_by,
          'invoice_id'             => $this->invoice_id ?? null,
          'pay_date'               => $this->pay_date ?? null,
          'currency_code'          => $this->currency_code ?? null,
          'created_at'             => $this->created_at,
          'updated_at'             => $this->updated_at,
          'invoice'               => InvoiceResource::make($this->invoice),
          'pay_amount'            => $this->pay_amount,
        ];
    }
}
