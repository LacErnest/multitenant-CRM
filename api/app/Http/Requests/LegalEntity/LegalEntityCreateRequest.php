<?php

namespace App\Http\Requests\LegalEntity;

use App\DTO\Banks\EuropeanBankDTO;
use App\Enums\Country;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Rules\VatNumberRule;
use Illuminate\Validation\Validator;

class LegalEntityCreateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'name' => [
              'required',
              'string',
              'max:128',
          ],
          'vat_number' => [
              'required',
              'string',
              'max:20',
              $this->input('legal_entity_address.country') ? new VatNumberRule($this->input('vat_number'), $this->input('legal_entity_address.country')) : null,
          ],
          'legal_entity_address' => [
              $this->addressRules('legal_entity_address'),
          ],
          'european_bank.name' => [
              'required',
              'string',
              'max:128',
          ],
          'european_bank.iban' => [
              'required',
              'string',
              'max:30',
          ],
          'european_bank.bic' => [
              'required',
              'string',
              'max:30',
          ],
          'european_bank.bank_address' => [
              $this->addressRules('european_bank.bank_address'),
          ],
          'american_bank.name' => [
              'required_with:american_bank.account_number,american_bank.routing_number',
              'nullable',
              'string',
              'max:128',
          ],
          'american_bank.account_number' => [
              'required_with:american_bank.name,american_bank.routing_number',
              'nullable',
              'string',
              'max:30',
          ],
          'american_bank.routing_number' => [
              'required_with:american_bank.name,american_bank.account_number',
              'nullable',
              'string',
              'max:30',
          ],
          'american_bank.usa_account_number' => [
              'required_with:american_bank.name,american_bank.usa_routing_number',
              'nullable',
              'string',
              'max:30',
          ],
          'american_bank.usa_routing_number' => [
              'required_with:american_bank.name,american_bank.usa_account_number',
              'nullable',
              'string',
              'max:30',
          ],
          'american_bank.bank_address' => [
              $this->input('american_bank.name') ? $this->addressRules('american_bank.bank_address') : null,
          ],
          'usdc_wallet_address'=> [
              'nullable',
              'string',
              'max:100',
          ],

        ];
    }

    private function addressRules(string $property)
    {
        $rules = $this->validate([
          $property . '.addressline_1' => [
              'required',
              'string',
              'max:128',
          ],
          $property . '.addressline_2' => [
              'nullable',
              'string',
              'max:128',
          ],
          $property . '.city' => [
              'required',
              'string',
              'max:128',
          ],
          $property . '.region' => [
              'nullable',
              'string',
              'max:128',
          ],
          $property . '.postal_code' => [
              'required',
              'string',
              'max:128',
          ],
          $property . '.country' => [
              'required',
              'numeric',
              'enum:' . Country::class,
          ],
        ]);

        return $rules;
    }
}
