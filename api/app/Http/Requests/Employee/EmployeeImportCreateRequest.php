<?php

namespace App\Http\Requests\Employee;

use App\Enums\CurrencyCode;
use App\Enums\EmployeeStatus;
use App\Enums\EmployeeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeImportCreateRequest extends FormRequest
{
    protected string $companyId;

    public function __construct(string $companyId)
    {
        $this->companyId = $companyId;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
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
                'integer',
                'enum:' . EmployeeType::class,
            ],
            'status'        => [
                'integer',
                'enum:' . EmployeeStatus::class,
            ],
            'salary'        => [
                'nullable',
                'numeric',
                'regex:/^\d*(\.\d{1,2})?$/',
                'between:0.01,999999.99',
            ],
            'working_hours' => [
                'numeric',
                'nullable',
                'min:1',
                'max:450',
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:128',
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
                    ->where('company_id', $this->companyId),
            ],
            'is_pm' => [
                'boolean',
                'nullable',
            ],
        ];
    }
}
