<?php

namespace App\Http\Resources\Commission;

use App\Enums\CommissionPaymentLogStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionPaymentLogResource extends JsonResource
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
          'sales_person_name' => $this->salesPerson->name,
          'sales_person_id' => $this->sales_person_id,
          'amount' => $this->amount,
          'status' => $this->approved ?? $this->status ?? CommissionPaymentLogStatus::paid()->getIndex(),
          'paid_at' => $this->created_at ?? null
        ];
    }
}
