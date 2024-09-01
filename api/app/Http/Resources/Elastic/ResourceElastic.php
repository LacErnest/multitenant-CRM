<?php

namespace App\Http\Resources\Elastic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourceElastic extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->syncOriginal();
        return
          [
              'id'                => $this->id,
              'name'              => $this->name,
              'first_name'        => $this->first_name,
              'last_name'         => $this->last_name,
              'email'             => $this->email,
              'type'              => $this->type,
              'status'            => $this->status,
              'tax_number'        => $this->tax_number,
              'default_currency'  => $this->default_currency,
              'hourly_rate'       => $this->hourly_rate ?? null,
              'daily_rate'        => $this->daily_rate ?? null,
              'phone_number'      => $this->phone_number,
              'address_id'        => $this->address_id ?? null,
              'addressline_1'     => $this->address_id ? $this->address->addressline_1 : null,
              'addressline_2'     => $this->address_id ? $this->address->addressline_2 : null,
              'city'              => $this->address_id ? $this->address->city : null,
              'region'            => $this->address_id ? $this->address->region : null,
              'postal_code'       => $this->address_id ? $this->address->postal_code : null,
              'country'           => $this->address_id ? $this->address->country : null,
              'job_title'         => $this->job_title ? $this->job_title : null,
              'created_at'        => $this->created_at->timestamp,
              'updated_at'        => $this->updated_at->timestamp ?? null,
              'company_id'        => getTenantWithConnection(),
              'can_be_borrowed'   => (bool)$this->can_be_borrowed,
              'legal_entity_id'   => $this->legal_entity_id,
              'legal_entity'      => $this->legalEntity ? $this->legalEntity->name : null,
              'non_vat_liable'    => (bool)$this->non_vat_liable,
          ];
    }
}
