<?php

namespace App\Http\Resources\Contact;

use App\Http\Resources\XeroEntity\XeroEntityStorageResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ContactResource extends JsonResource
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
          'id'                => $this->id,
          'name'              => $this->name,
          'first_name'        => $this->first_name,
          'last_name'         => $this->last_name,
          'email'             => $this->email,
          'phone_number'      => $this->phone_number,
          'department'        => $this->department,
          'title'             => $this->title,
          'gender'            => $this->gender,
          'linked_in_profile' => $this->linked_in_profile ?? null,
          'primary_contact'   => $this->id == $this->customer->primary_contact_id,
          'xero_id'           => XeroEntityStorageResource::collection($this->xeroEntities),
        ];
    }
}
