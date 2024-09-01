<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Requests\BaseRequest;
use App\Models\PurchaseOrder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateIndependentPORequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $userRole = auth()->user()->role;
        if (UserRole::isSales($userRole) || UserRole::isHr($userRole) || UserRole::isOwner_read($userRole)
        || UserRole::isPm_restricted($userRole)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'date' => [
              'required',
              'date',
          ],
          'delivery_date' => [
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
          'resource_id' => [
              'required',
              'uuid',
          ],
          'manual_input' => [
              'boolean',
              'required',
          ],
          'legal_entity_id' => [
              'nullable',
              'uuid',
              Rule::exists('company_legal_entity', 'legal_entity_id')
                  ->where('company_id', $this->route('company_id')),
          ],
          'payment_terms' => [
              'required',
              'integer',
              'between:1,255',
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
        $this->checkEuropeanTaxNumber($validator, PurchaseOrder::class);

        $validator->after(function ($validator) {
            if (!empty($this->legal_entity_id) && (UserRole::isPm(auth()->user()->role) || UserRole::isPm_restricted(auth()->user()->role))) {
                $validator->errors()->add('legal_entity_id', 'You are not allowed to set the legal entity.');
            }
        });
    }
}
