<?php

namespace App\Http\Requests\Employee;

use App\Enums\Country;
use App\Enums\CurrencyCode;
use App\Enums\EmployeeStatus;
use App\Enums\EmployeeType;
use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;


class EmployeeCreateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isSales = UserRole::isSales(auth()->user()->role);
        $readOwner = UserRole::isOwner_read(auth()->user()->role);
        $isRestrictedPM = UserRole::isPm_restricted(auth()->user()->role);

        return !$isSales && !$readOwner && !$isRestrictedPM;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => [
                'string',
                'max:128',
                'required',
            ],
            'last_name' => [
                'string',
                'max:128',
                'required',
            ],
            'email' => [
                'nullable',
                'email',
                'max:128',
                'unique:tenant.employees,email',
            ],
            'type'          => [
                'nullable',
                'integer',
                'enum:' . EmployeeType::class,
            ],
            'status'        => [
                'nullable',
                'integer',
                'enum:' . EmployeeStatus::class,
            ],
            'salary'        => [
                'nullable',
                'numeric',
                'regex:/^\d*(\.\d{1,2})?$/',
                'between:0.01,999999.99',
                'required_if:type,1',
            ],
            'working_hours' => [
                'numeric',
                'nullable',
                'min:1',
                'max:450',
                'required_if:type,1',
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:128',
            ],
            'addressline_1' => [
                'nullable',
                'string',
                'max:128',
            ],
            'addressline_2' => [
                'nullable',
                'string',
                'max:128',
            ],
            'city' => [
                'nullable',
                'string',
                'max:128',
            ],
            'region' => [
                'nullable',
                'string',
                'max:128',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:128',
            ],
            'country' => [
                'nullable',
                'required_if:type,1',
                'numeric',
                'enum:' . Country::class,
            ],
            'linked_in_profile' => [
                'string',
                'nullable',
                'regex:/http(s)?:\/\/([\w]+\.)?linkedin\.com\/in\/[A-z0-9_-]+\/?/'
            ],
            'facebook_profile' => [
                'string',
                'nullable',
                'regex:/http(s)?:\/\/([\w]+\.)?facebook\.com\/[A-z0-9_.-]+\/?/'
            ],
            'role' => [
                'string',
                'nullable',
                'max:128',
            ],
            'started_at' => [
                'date',
                'nullable',
            ],
            'default_currency' => [
                'numeric',
                'required',
                'enum:' . CurrencyCode::class,
            ],
            'legal_entity_id' => [
                'required',
                'uuid',
                Rule::exists('company_legal_entity', 'legal_entity_id')
                    ->where('company_id', $this->route('company_id')),
            ],
            'is_pm' => [
                'boolean',
                'nullable',
            ],
            'overhead_employee' => [
                'boolean',
                'required',
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
            if ($this->overhead_employee && UserRole::isPm(auth()->user()->role)) {
                $validator->errors()->add('overhead_employee', 'You are not allowed to create overhead employees');
            }
        });
    }
}
