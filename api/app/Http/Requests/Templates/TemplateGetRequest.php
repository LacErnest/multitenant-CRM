<?php

namespace App\Http\Requests\Templates;

use App\Enums\TablePreferenceType;
use App\Enums\TemplateType;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Validator;

class TemplateGetRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isOwner(auth()->user()->role) || UserRole::isAccountant(auth()->user()->role) ||
          UserRole::isAdmin(auth()->user()->role) || UserRole::isHr(auth()->user()->role) ||
          UserRole::isOwner_read(auth()->user()->role)) {
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
            if (!in_array($this->entity, TemplateType::getValues())) {
                $validator->errors()->add('Entity', 'Wong entity data');
            }

            if (!in_array($this->type, ['docx','pdf'])) {
                $validator->errors()->add('Type', 'Wong type data');
            }
        });
        return;
    }
}
