<?php

namespace App\Http\Resources\Elastic;

use Illuminate\Http\Resources\Json\JsonResource;

class ContactElastic extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return
          [
              'id' => $this->id,
              'first_name' => $this->first_name,
              'last_name' => $this->last_name,
              'name' => $this->first_name.' '.$this->last_name,
              'email' => $this->email,
              'phone_number' => $this->phone_number,
              'primary_contact' => $this->id == $this->customer->primary_contact_id ? true : false,
              'department' => $this->department ?? null,
              'title' => $this->title ?? null,
              'gender' => $this->gender,
              'linked_in_profile' => $this->linked_in_profile ?? null,
              'customer_id' => $this->customer_id,
              'customer' => $this->customer ? $this->customer->name : null,
              'created_at' => $this->created_at->timestamp,
              'updated_at' => $this->updated_at->timestamp ?? null
          ];
    }
}
