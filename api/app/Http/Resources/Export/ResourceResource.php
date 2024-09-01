<?php

namespace App\Http\Resources\Export;

use App\Enums\Country;
use App\Enums\CurrencyCode;

class ResourceResource extends ExtendedJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $isCurrencyUSD = $this->default_currency == CurrencyCode::USD()->getIndex();
        $isResourceFromUsa = $this->address->country && $this->address->country == Country::UnitedStatesOfAmerica()->getIndex();

        return [
        'VAR_NAME'                => $this->name ?? null,
        'VAR_FIRST_NAME'          => $this->first_name ?? null,
        'VAR_LAST_NAME'           => $this->last_name ?? null,
        'VAR_EMAIL'               => $this->email ?? null,
        'VAR_TAX_NUMBER'          => $this->tax_number ?? null,
        'VAR_CURRENCY'            => !($this->default_currency === null) ?
            CurrencyCode::make($this->default_currency)->getValue() :
            null,
        'VAR_WEBSITE'             => $this->website ?? null,
        'VAR_PHONE_NUMBER'        => $this->phone_number ?? null,
        'VAR_ADDRESSLINE_1'       => $this->address->addressline_1 ?? null,
        'VAR_ADDRESSLINE_2'       => $this->address->addressline_2 ?? null,
        'VAR_CITY'                => $this->address->city ?? null,
        'VAR_REGION'              => $this->address->region ?? null,
        'VAR_POSTAL_CODE'         => $this->address->postal_code ?? null,
        'VAR_COUNTRY'             => $this->address->country ?
            Country::make($this->address->country)->getValue() :
            null,
        'VAR_DATE'                => date('d/m/Y'),
        'VAR_LEGAL_NAME'          => $this->legalEntity->name ?? null,
        'VAR_LEGAL_VAT'           => $this->legalEntity->vat_number ?? null,
        'VAR_LEGAL_ADDRESSLINE_1' => $this->legalEntity->address->addressline_1 ?? null,
        'VAR_LEGAL_ADDRESSLINE_2' => $this->legalEntity->address->addressline_2 ?? null,
        'VAR_LEGAL_CITY'          => $this->legalEntity->address->city ?? null,
        'VAR_LEGAL_REGION'        => $this->legalEntity->address->region ?? null,
        'VAR_LEGAL_POSTAL_CODE'   => $this->legalEntity->address->postal_code ?? null,
        'VAR_LEGAL_COUNTRY'       => $this->legalEntity->address->country ?
            Country::make($this->legalEntity->address->country)->getValue() :
            null,
        'VAR_BANK_ADDRESSLINE_1'  => $isCurrencyUSD ? $this->legalEntity->americanBank->address->addressline_1 :
            $this->legalEntity->europeanBank->address->addressline_1,
        'VAR_BANK_ADDRESSLINE_2'  => $isCurrencyUSD ? $this->legalEntity->americanBank->address->addressline_2 :
            $this->legalEntity->europeanBank->address->addressline_2,
        'VAR_BANK_CITY'           => $isCurrencyUSD ? $this->legalEntity->americanBank->address->city :
            $this->legalEntity->europeanBank->address->city,
        'VAR_BANK_REGION'         => $isCurrencyUSD ? $this->legalEntity->americanBank->address->region :
            $this->legalEntity->europeanBank->address->region,
        'VAR_BANK_POSTAL_CODE'    => $isCurrencyUSD ? $this->legalEntity->americanBank->address->postal_code :
            $this->legalEntity->europeanBank->address->postal_code,
        'VAR_BANK_COUNTRY'        => $isCurrencyUSD ? ($this->legalEntity->americanBank->address->country ?
            Country::make($this->legalEntity->americanBank->address->country)->getValue() : null)
            : ($this->legalEntity->europeanBank->address->country ?
                Country::make($this->legalEntity->europeanBank->address->country)->getValue() : null),
        $this->legalEntity->europeanBank->address->country,
        'VAR_BANK_NAME'           => $isCurrencyUSD ? $this->legalEntity->americanBank->name :
            $this->legalEntity->europeanBank->name,
        'VAR_BANK_IBAN'           => $this->legalEntity->europeanBank->iban ?? null,
        'VAR_BANK_BIC'            => $this->legalEntity->europeanBank->bic ?? null,
        'VAR_BANK_ACCOUNT_NR'     => $isResourceFromUsa && $this->legalEntity->americanBank->usa_account_number ?
            $this->legalEntity->americanBank->usa_account_number : $this->legalEntity->americanBank->account_number,
        'VAR_BANK_ROUTING_NR'     => $isResourceFromUsa && $this->legalEntity->americanBank->usa_routing_number ?
            $this->legalEntity->americanBank->usa_routing_number : $this->legalEntity->americanBank->routing_number,
        ];
    }
}
