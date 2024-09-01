<?php

namespace App\Http\Requests\Export;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Validator;

class ExportOrderRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isSales(auth()->user()->role) || UserRole::isHr(auth()->user()->role)) {
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
            if (!in_array($this->type, ['docx','pdf','xlsx'])) {
                $validator->errors()->add('Type', 'Wrong type data');
            }
        });
    }
}
