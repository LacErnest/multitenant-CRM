<?php

namespace App\Http\Requests\Commission;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\CommissionPaymentLog;
use Illuminate\Validation\Validator;


class ConfirmPaymentBySalespersonRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isSales(auth()->user()->role)) {
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
            $commissionPaymentLog = CommissionPaymentLog::findOrFail($this->paymentLogId);

            if ($commissionPaymentLog->approved == true) {
                $validator->errors()->add('commission_payment_log_id', 'the log has already been confirmed');
            }
        });
    }
}
