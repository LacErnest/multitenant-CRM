<?php

namespace App\Http\Requests\Commission;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\CommissionPaymentLog;
use App\Services\CommissionService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;



class CreateCommissionPaymentLogRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isAccountant(auth()->user()->role) || UserRole::isAdmin(auth()->user()->role)) {
            return true;
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'sales_person_id' => [
              'required',
              'nullable',
              Rule::exists('users', 'id')
                  ->whereIn('role', [UserRole::sales()->getIndex()]),
          ],
          'amount' => [
              'required',
              'numeric',
          ],
          'approved' => [
              'boolean'
          ]
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
        $validator->after(function ($validator) {
            $totalOpenAmountForCommission = CommissionService::getCommissionTotalOpenAmount($this->sales_person_id);
            if ($this->amount > $totalOpenAmountForCommission || $this->amount < CommissionPaymentLog::MINIMUM_COMMISSION_PAYMENT_AMOUNT) {
                $validator->errors()->add('amount', Str::replaceArray('?', [CommissionPaymentLog::MINIMUM_COMMISSION_PAYMENT_AMOUNT, $totalOpenAmountForCommission], 'Please enter a value between ? and ?'));
            }
        });
    }
}
