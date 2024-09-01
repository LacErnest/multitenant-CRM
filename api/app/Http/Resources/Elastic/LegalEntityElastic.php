<?php

namespace App\Http\Resources\Elastic;

use Illuminate\Http\Resources\Json\JsonResource;

class LegalEntityElastic extends JsonResource
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
          'id'                            => $this->id,
          'name'                          => $this->name,
          'vat_number'                    => $this->vat_number,
          'legal_entity_xero_config_id'   => $this->legal_entity_xero_config_id,
          'legal_entity_setting_id'       => $this->legal_entity_setting_id,
          'address_id'                    => $this->address_id,
          'addressline_1'                 => $this->address_id ? $this->address->addressline_1 : null,
          'addressline_2'                 => $this->address_id ? $this->address->addressline_2 : null,
          'city'                          => $this->address_id ? $this->address->city : null,
          'region'                        => $this->address_id ? $this->address->region : null,
          'postal_code'                   => $this->address_id ? $this->address->postal_code : null,
          'country'                       => $this->address_id ? $this->address->country : null,
          'european_bank_id'              => $this->european_bank_id,
          'european_bank_name'            => $this->european_bank_id ? $this->europeanBank->name : null,
          'european_bank_iban'            => $this->european_bank_id ? $this->europeanBank->iban : null,
          'european_bank_bic'             => $this->european_bank_id ? $this->europeanBank->bic : null,
          'european_bank_address_id'      => $this->european_bank_id ? $this->europeanBank->address_id : null,
          'european_bank_addressline_1'   => $this->european_bank_id ? $this->europeanBank->address->addressline_1 : null,
          'european_bank_addressline_2'   => $this->european_bank_id ? $this->europeanBank->address->addressline_2 : null,
          'european_bank_city'            => $this->european_bank_id ? $this->europeanBank->address->city : null,
          'european_bank_region'          => $this->european_bank_id ? $this->europeanBank->address->region : null,
          'european_bank_postal_code'     => $this->european_bank_id ? $this->europeanBank->address->postal_code : null,
          'european_bank_country'         => $this->european_bank_id ? $this->europeanBank->address->country : null,
          'american_bank_id'              => $this->american_bank_id,
          'american_bank_name'            => $this->american_bank_id ? $this->americanBank->name : null,
          'american_bank_account_number'  => $this->american_bank_id ? $this->americanBank->account_number : null,
          'american_bank_routing_number'  => $this->american_bank_id ? $this->americanBank->routing_number: null,
          'american_bank_address_id'      => $this->american_bank_id ? $this->americanBank->address_id : null,
          'american_bank_addressline_1'   => $this->american_bank_id ? $this->americanBank->address->addressline_1 : null,
          'american_bank_addressline_2'   => $this->american_bank_id ? $this->americanBank->address->addressline_2 : null,
          'american_bank_city'            => $this->american_bank_id ? $this->americanBank->address->city : null,
          'american_bank_region'          => $this->american_bank_id ? $this->americanBank->address->region : null,
          'american_bank_postal_code'     => $this->american_bank_id ? $this->americanBank->address->postal_code : null,
          'american_bank_country'         => $this->american_bank_id ? $this->americanBank->address->country : null,
          'usdc_wallet_address'                   => $this->usdc_wallet_address,
          'created_at'                    => $this->created_at->timestamp,
          'updated_at'                    => $this->updated_at->timestamp,
          'deleted_at'                    => $this->deleted_at->timestamp ?? null,
        ];
    }
}
