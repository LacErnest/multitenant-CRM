<?php

namespace App\Http\Resources\Elastic;

use Illuminate\Http\Resources\Json\JsonResource;

class CommissionPaymentLogElastic extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->syncOriginal();

        return
          [
              'id' => $this->id,
              'sales_person_id' => $this->sales_person_id,
              'amount' => $this->amount,
              'is_confirmed' => (boolean)$this->approved,
              'payment_date' => $this->payment_date->timestamp ?? null
          ];
    }
}
