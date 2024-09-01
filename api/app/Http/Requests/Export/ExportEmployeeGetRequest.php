<?php

namespace App\Http\Requests\Export;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Validator;

class ExportEmployeeGetRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isSales = UserRole::isSales(auth()->user()->role);
        $isRestrictedPM = UserRole::isPm_restricted(auth()->user()->role);

        return !$isSales && !$isRestrictedPM;
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
            if (!in_array($this->extension, ['docx', 'pdf'])) {
                $validator->errors()->add('Type', 'Wrong type data');
            }

            if (!in_array($this->type, ['employee', 'contractor'])) {
                $validator->errors()->add('Type', 'Wrong type data');
            }
        });
    }
}
