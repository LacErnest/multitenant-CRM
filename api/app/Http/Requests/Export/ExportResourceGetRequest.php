<?php

namespace App\Http\Requests\Export;

use App\Enums\TemplateType;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Validator;

class ExportResourceGetRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (
          (auth()->user() === null) || (UserRole::isOwner(auth()->user()->role) || UserRole::isAccountant(auth()->user()->role) ||
              UserRole::isAdmin(auth()->user()->role) || UserRole::isPm(auth()->user()->role) ||
              UserRole::isHr(auth()->user()->role) || UserRole::isPm_restricted(auth()->user()->role))
        ) {
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
            if (!in_array($this->extension, ['docx','pdf'])) {
                $validator->errors()->add('Extension', 'Wrong type data');
            }
            if (!in_array($this->type, ['contractor','freelancer','nda'])) {
                $validator->errors()->add('Type', 'Wrong type data');
            }
        });
        return;
    }
}
