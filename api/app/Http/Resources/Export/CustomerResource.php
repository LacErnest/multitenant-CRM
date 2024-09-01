<?php

namespace App\Http\Resources\Export;

use App\Enums\Country;
use App\Enums\CurrencyCode;

class CustomerResource extends ExtendedJsonResource
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
          'VAR_NAME'          => $this->name ?? null,
          'VAR_EMAIL'         => $this->email ?? null,
          'VAR_TAX_NUMBER'    => $this->tax_number ?? null,
          'VAR_CURRENCY'      => !($this->default_currency === null) ?
              CurrencyCode::make($this->default_currency)->getValue() :
              null,
          'VAR_WEBSITE'       => $this->website ?? null,
          'VAR_PHONE_NUMBER'  => $this->phone_number ?? null,
          'VAR_ADDRESSLINE_1' => $this->billing_address->addressline_1 ?? null,
          'VAR_ADDRESSLINE_2' => $this->billing_address->addressline_2 ?? null,
          'VAR_CITY'          => $this->billing_address->city ?? null,
          'VAR_REGION'        => $this->billing_address->region ?? null,
          'VAR_POSTAL_CODE'   => $this->billing_address->postal_code ?? null,
          'VAR_COUNTRY'       => $this->billing_address->country ?
              Country::make($this->billing_address->country)->getValue() :
              null,
          'VAR_S_FIRST_NAME'  => $this->salesPerson->first_name ?? null,
          'VAR_S_LAST_NAME'   => $this->salesPerson->last_name ?? null,
          'VAR_S_EMAIL'       => $this->salesPerson->email ?? null,
          'VAR_DATE'          => date('d/m/Y'),
          'VAR_CC_FIRST'      => $this->primaryContact->first_name ?? null,
          'VAR_CC_LAST'       => $this->primaryContact->last_name ?? null,
          'VAR_CC_EMAIL'      => $this->primaryContact->email ?? null,
          'VAR_CC_PHONE'      => $this->primaryContact->phone_number ?? null,
          'VAR_CC_TITLE'      => $this->primaryContact->title ?? null,
        ];
    }
}
