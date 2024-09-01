<?php

namespace App\Http\Requests\Export;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExportCustomerGetRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isPm(auth()->user()->role) || UserRole::isHr(auth()->user()->role) ||
          UserRole::isPm_restricted(auth()->user()->role)) {
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
          'legal_entity_id' => [
              'required',
              'uuid',
              Rule::exists('company_legal_entity', 'legal_entity_id')
                  ->where('company_id', $this->route('company_id')),
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
        $validator->after(function ($validator) {
            if (!in_array($this->extension, ['docx','pdf'])) {
                $validator->errors()->add('Type', 'Wrong type data');
            }

            if (!in_array($this->type, ['customer'])) {
                $validator->errors()->add('Type', 'Wrong type data');
            }
        });

        return;
    }
}
