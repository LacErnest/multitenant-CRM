<?php

namespace App\Http\Resources\Elastic;

use Illuminate\Http\Resources\Json\JsonResource;
use Tenancy\Facades\Tenancy;

class CustomerElastic extends JsonResource
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
        $this->load('billing_address', 'operational_address');
        return
          [
              'id'                        => $this->id,
              'company_id'                => $this->company ? $this->company_id : null,
              'company'                   => $this->company ? $this->company->name : null,
              'status'                    => $this->status,
              'name'                      => $this->name,
              'description'               => $this->description ?? null,
              'industry'                  => $this->industry ?? null,
              'email'                     => $this->email,
              'tax_number'                => $this->tax_number,
              'default_currency'          => $this->default_currency,
              'website'                   => $this->website,
              'phone_number'              => $this->phone_number,
              'sales_person'              => $this->salesPerson ? $this->salesPerson->name : null,
              'sales_person_id'           => $this->salesPerson ? $this->salesPerson->id : null,
              'primary_contact_id'        => $this->primaryContact ? $this->primaryContact->id : null,
              'primary_contact'           => $this->primaryContact ? $this->primaryContact->name : null,
              'average_collection_period' => $this->average_collection_period ?? null,
              'legacy_customer'           => (bool)$this->legacy_customer && $this->company,
              'billing_address_id'        => $this->billing_address_id ?? null,
              'billing_addressline_1'     => $this->billing_address_id ? $this->billing_address->addressline_1 : null,
              'billing_addressline_2'     => $this->billing_address_id ? $this->billing_address->addressline_2 : null,
              'billing_city'              => $this->billing_address_id ? $this->billing_address->city : null,
              'billing_region'            => $this->billing_address_id ? $this->billing_address->region : null,
              'billing_postal_code'       => $this->billing_address_id ? $this->billing_address->postal_code : null,
              'billing_country'           => $this->billing_address_id ? $this->billing_address->country : null,
              'operational_address_id'    => $this->operational_address_id ?? null,
              'operational_addressline_1' => $this->operational_address_id ? $this->operational_address->addressline_1 : null,
              'operational_addressline_2' => $this->operational_address_id ? $this->operational_address->addressline_2 : null,
              'operational_city'          => $this->operational_address_id ? $this->operational_address->city : null,
              'operational_region'        => $this->operational_address_id ? $this->operational_address->region : null,
              'operational_postal_code'   => $this->operational_address_id ? $this->operational_address->postal_code : null,
              'operational_country'       => $this->operational_address_id ? $this->operational_address->country : null,
              'created_at'                => $this->created_at->timestamp,
              'updated_at'                => $this->updated_at->timestamp ?? null,
              'contacts'                  => $this->contacts,
              'non_vat_liable'            => (bool)$this->non_vat_liable,
          ];
    }
}
