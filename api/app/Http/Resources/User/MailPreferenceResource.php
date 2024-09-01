<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class MailPreferenceResource extends JsonResource
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
          'user_id' => $this->user_id,
          'customers' => $this->customers == 1 ? true : false,
          'quotes' => $this->quotes == 1 ? true : false,
          'invoices' => $this->invoices == 1 ? true : false,
          'created_at' => $this->created_at,
          'updated_at' => $this->updated_at ?? null,
        ];
    }
}
