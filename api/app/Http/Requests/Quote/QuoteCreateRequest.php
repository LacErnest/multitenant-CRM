<?php

namespace App\Http\Requests\Quote;

use App\Enums\CurrencyCode;
use App\Enums\DownPaymentAmountType;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuoteCreateRequest extends BaseRequest
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
              'max:191'
          ],
          'contact_id' => [
              'required',
              'uuid',
              Rule::exists('contacts', 'id'),
          ],
          'sales_person_id.*' => [
              'required',
              'uuid',
              Rule::exists('users', 'id')
                  ->where(function ($query) {
                      $query->where('company_id', getTenantWithConnection())->orWhereNull('company_id');
                  })
                  ->whereIn('role', [UserRole::sales()->getIndex(), UserRole::owner()->getIndex(), UserRole::admin()->getIndex()]),
          ],
          'date' => [
              'required',
              'date',
          ],
          'expiry_date' => [
              'required',
              'date',
              'after_or_equal:date',
          ],
          'reference' => [
              'string',
              'nullable',
              'max:50',
          ],
          'currency_code' => [
              'integer',
              'required',
              'enum:' . CurrencyCode::class,
          ],
          'manual_input' => [
              'boolean',
              'required',
          ],
          'down_payment' => [
              'nullable',
              'integer',
          ],
          'down_payment_type' => 'nullable|integer|enum:'. DownPaymentAmountType::class,
          'legal_entity_id' => [
              'required',
              'uuid',
              Rule::exists('company_legal_entity', 'legal_entity_id')
                  ->where('company_id', $this->route('company_id')),
          ],
          'master' => [
              'boolean'
          ],
          'vat_status' => [
              'nullable',
              'integer',
              'enum:' . VatStatus::class,
          ],
          'vat_percentage' => [
              'numeric',
              'nullable',
              'min:0',
              'max:100',
          ],
          'second_sales_person_id.*' => [
              'nullable',
              'uuid',
              Rule::exists('users', 'id')
                  ->where(function ($query) {
                      $query->where('company_id', getTenantWithConnection())->orWhereNull('company_id');
                  })
                  ->whereIn('role', [UserRole::sales()->getIndex(), UserRole::owner()->getIndex(), UserRole::admin()->getIndex()]),
          ],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        //
    }

    /**
     * After validation passed
     */
    protected function passedValidation()
    {
        $this->merge(
            [
              'master' => $this->input('master', false)
            ]
        );
    }
}
