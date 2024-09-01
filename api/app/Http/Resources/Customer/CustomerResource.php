<?php

namespace App\Http\Resources\Customer;

use App\Http\Resources\Contact\ContactResource;
use App\Http\Resources\XeroEntity\XeroEntityStorageResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Tenancy\Facades\Tenancy;

class CustomerResource extends JsonResource
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
              'id'                        => $this->id,
              'company_id'                => $this->company_id ?? null,
              'company'                   => $this->company ? $this->company->name : null,
              'name'                      => $this->name,
              'description'               => $this->description ?? null,
              'payment_due_date'          => $this->payment_due_date,
              'industry'                  => $this->industry ?? null,
              'email'                     => $this->email,
              'status'                    => $this->status,
              'tax_number'                => $this->tax_number,
              'default_currency'          => $this->default_currency,
              'website'                   => $this->website,
              'phone_number'              => $this->phone_number,
              'sales_person'              => $this->salesPerson ? [
                  'id'    => $this->salesPerson->id,
                  'name'  => $this->salesPerson->name,
                  'email' => $this->salesPerson->email
              ] : null,
              'average_collection_period' => $this->average_collection_period ?? null,
              'billing_address_id'        => $this->billing_address_id ?? null,
              'billing_addressline_1'     => $this->billing_address ? $this->billing_address->addressline_1 : null,
              'billing_addressline_2'     => $this->billing_address ? $this->billing_address->addressline_2 : null,
              'billing_city'              => $this->billing_address ? $this->billing_address->city : null,
              'billing_region'            => $this->billing_address ? $this->billing_address->region : null,
              'billing_postal_code'       => $this->billing_address ? $this->billing_address->postal_code : null,
              'billing_country'           => $this->billing_address ? $this->billing_address->country : null,
              'operational_address_id'    => $this->operational_address_id ?? null,
              'operational_addressline_1' => $this->operational_address ? $this->operational_address->addressline_1 : null,
              'operational_addressline_2' => $this->operational_address ? $this->operational_address->addressline_2 : null,
              'operational_city'          => $this->operational_address ? $this->operational_address->city : null,
              'operational_region'        => $this->operational_address ? $this->operational_address->region : null,
              'operational_postal_code'   => $this->operational_address ? $this->operational_address->postal_code : null,
              'operational_country'       => $this->operational_address ? $this->operational_address->country : null,
              'is_same_address'           => $this->billing_address_id == $this->operational_address_id,
              'intra_company'             => $this->intra_company,
              'created_at'                => $this->created_at ?? null,
              'updated_at'                => $this->updated_at ?? null,
              'contacts'                  => $this->contacts ? ContactResource::collection($this->contacts) : null,
              'xero_id'                   => XeroEntityStorageResource::collection($this->xeroEntities),
              'non_vat_liable'            => $this->non_vat_liable,
          ];
    }
}
