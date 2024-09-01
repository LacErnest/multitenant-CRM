<?php

namespace App\Http\Requests\Export;

use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\PurchaseOrder;
use App\Models\Resource;
use Illuminate\Validation\Validator;

class ExportPurchaseOrderRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isNotSP = !UserRole::isSales(auth()->user()->role);
        $isNotHR = !UserRole::isHr(auth()->user()->role);

        return $isNotHR && $isNotSP;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          //
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
            if (!in_array($this->type, ['docx','pdf'])) {
                $validator->errors()->add('Type', 'Wrong type data');
            }
        });
    }
}
