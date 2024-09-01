<?php

namespace App\Http\Requests\Customer;

use App\Enums\Country;
use App\Enums\CurrencyCode;
use App\Enums\CustomerStatus;
use App\Enums\IndustryType;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\Customer;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CustomerCreateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isPm(auth()->user()->role) || UserRole::isHr(auth()->user()->role) ||
          UserRole::isOwner_read(auth()->user()->role) || UserRole::isPm_restricted(auth()->user()->role)) {
            return false;
        }
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'name' => 'required|string|max:128|nullable',
          'email' => 'email|max:128|unique:customers,email|nullable',
          'status' => 'nullable|integer|enum:' . CustomerStatus::class,
          'description' => 'string|nullable|max:500',
          'industry' => 'integer|nullable|enum:' . IndustryType::class,
          'tax_number' => 'string|max:128|nullable',
          'default_currency' => 'numeric|required|enum:' . CurrencyCode::class,
          'website' => [
              'string',
              'max:128',
              'nullable',
              ['regex' => '/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/']
          ],
          'phone_number' => 'string|max:128|nullable',
          'sales_person_id' => [
              'nullable',
              Rule::exists('users', 'id')
                  ->where(function ($query) {
                      $query->where('company_id', getTenantWithConnection())->orWhereNull('company_id');
                  })
                  ->whereIn('role', [UserRole::sales()->getIndex(), UserRole::owner()->getIndex(), UserRole::admin()->getIndex()])
          ],
          'billing_addressline_1' => 'string|max:128|nullable',
          'billing_addressline_2' => 'string|max:128|nullable',
          'billing_city' => 'string|max:128|nullable',
          'billing_region' => 'string|max:128|nullable',
          'billing_postal_code' => 'string|max:128|nullable',
          'billing_country' => 'integer|required_if:is_same_address,0|enum:' . Country::class,
          'operational_addressline_1' => 'string|max:128|nullable',
          'operational_addressline_2' => 'string|max:128|nullable',
          'operational_city' => 'string|max:128|nullable',
          'operational_region' => 'string|max:128|nullable',
          'operational_postal_code' => 'string|max:128|nullable',
          'operational_country' => 'integer|nullable|required_if:is_same_address,1|enum:' . Country::class,
          'is_same_address' => 'boolean|required',
          'non_vat_liable' => 'boolean',
          'intra_company' => 'boolean',
          'payment_due_date' => 'nullable|integer',
          'intra_company' => [
              'boolean',
              'required',
          ],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $this->checkEuropeanTaxNumber($validator, Customer::class);
    }
}
