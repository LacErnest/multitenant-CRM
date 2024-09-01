<?php

namespace App\Http\Resources\LegalEntity;

use Illuminate\Http\Resources\Json\JsonResource;

class LegalEntityResource extends JsonResource
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
          'id'                            => $this->id,
          'name'                          => $this->name,
          'vat_number'                    => $this->vat_number,
          'legal_entity_xero_config_id'   => $this->legal_entity_xero_config_id,
          'legal_entity_setting_id'       => $this->legal_entity_setting_id,
          'legal_entity_address'          => $this->address ? [
              'id'            => $this->address->id,
              'addressline_1' => $this->address->addressline_1,
              'addressline_2' => $this->address->addressline_2,
              'city'          => $this->address->city,
              'region'        => $this->address->region,
              'postal_code'   => $this->address->postal_code,
              'country'       => $this->address->country,
          ] : null,
          'european_bank'                 => $this->europeanBank ? [
              'id'            => $this->europeanBank->id,
              'name'          => $this->europeanBank->name,
              'iban'          => $this->europeanBank->iban,
              'bic'           => $this->europeanBank->bic,
              'address_id'    => $this->europeanBank->address_id,
              'bank_address'  => $this->europeanBank->address ? [
                  'addressline_1' => $this->europeanBank->address->addressline_1,
                  'addressline_2' => $this->europeanBank->address->addressline_2,
                  'city'          => $this->europeanBank->address->city,
                  'region'        => $this->europeanBank->address->region,
                  'postal_code'   => $this->europeanBank->address->postal_code,
                  'country'       => $this->europeanBank->address->country,
              ] : null,
          ] : null,
          'american_bank'                 => $this->americanBank ? [
              'id'             => $this->americanBank->id,
              'name'           => $this->americanBank->name,
              'account_number' => $this->americanBank->account_number,
              'routing_number' => $this->americanBank->routing_number,
              'usa_account_number' => $this->americanBank->usa_account_number,
              'usa_routing_number' => $this->americanBank->usa_routing_number,
              'address_id'     => $this->americanBank->address_id,
              'bank_address'   => $this->americanBank->address ? [
                  'addressline_1'  => $this->americanBank->address->addressline_1,
                  'addressline_2'  => $this->americanBank->address->addressline_2,
                  'city'           => $this->americanBank->address->city,
                  'region'         => $this->americanBank->address->region,
                  'postal_code'    => $this->americanBank->address->postal_code,
                  'country'        => $this->americanBank->address->country,
              ] : null,
          ] : null,
          'usdc_wallet_address'       => $this->usdc_wallet_address,
          'created_at'                    => $this->created_at,
          'updated_at'                    => $this->updated_at,
          'deleted_at'                    => $this->deleted_at,
        ];
    }
}
