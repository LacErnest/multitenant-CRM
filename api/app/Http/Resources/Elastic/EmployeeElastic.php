<?php

namespace App\Http\Resources\Elastic;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeElastic extends JsonResource
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

        return [
          'id'                => $this->id,
          'first_name'        => $this->first_name,
          'last_name'         => $this->last_name,
          'name'              => $this->name,
          'email'             => $this->email,
          'type'              => $this->type,
          'status'            => $this->status,
          'salary'            => $this->salary,
          'working_hours'     => $this->working_hours,
          'phone_number'      => $this->phone_number,
          'linked_in_profile' => $this->linked_in_profile ?? null,
          'facebook_profile'  => $this->facebook_profile ?? null,
          'started_at'        => $this->started_at->timestamp ?? null,
          'address_id'        => $this->address_id ?? null,
          'addressline_1'     => $this->address_id ? $this->address->addressline_1 : null,
          'addressline_2'     => $this->address_id ? $this->address->addressline_2 : null,
          'city'              => $this->address_id ? $this->address->city : null,
          'region'            => $this->address_id ? $this->address->region : null,
          'postal_code'       => $this->address_id ? $this->address->postal_code : null,
          'country'           => $this->address_id ? $this->address->country : null,
          'created_at'        => $this->created_at->timestamp,
          'updated_at'        => $this->updated_at->timestamp ?? null,
          'role'              => $this->role ?? null,
          'default_currency'  => $this->default_currency,
          'company_id'        => getTenantWithConnection(),
          'can_be_borrowed'   => (bool)$this->can_be_borrowed,
          'legal_entity_id'   => $this->legal_entity_id,
          'legal_entity'      => $this->legalEntity ? $this->legalEntity->name : null,
          'is_pm'             => (bool)$this->is_pm,
          'overhead_employee' => (bool)$this->overhead_employee,
        ];
    }
}
