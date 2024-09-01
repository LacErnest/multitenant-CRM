<?php

namespace App\Http\Requests\Customer;

use App\Enums\IndustryType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerImportUpdateRequest extends FormRequest
{
    protected string $companyId;
    protected string $existingCustomerId;

    public function __construct(string $companyId, string $existingCustomerId)
    {
        $this->companyId = $companyId;
        $this->existingCustomerId = $existingCustomerId;
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
          'name' => 'required|string|max:128|nullable',
          'email' => 'email|nullable|max:128|unique:customers,email,' . $this->existingCustomerId,
          'description' => 'string|nullable|max:500',
          'industry' => 'integer|nullable|enum:' . IndustryType::class,
          'tax_number' => 'string|max:128|nullable',
          'website' => [
              'string',
              'max:128',
              'nullable',
              ['regex' => '/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/']
          ],
          'phone_number' => 'string|max:128|nullable',
          'sales_person_id' => [
              'nullable',
              Rule::exists('users', 'id')
                  ->where(function ($query) {
                      $query->where('company_id', $this->companyId)->orWhereNull('company_id');
                  })
                  ->whereIn('role', [UserRole::sales()->getIndex(), UserRole::owner()->getIndex(), UserRole::admin()->getIndex()])
          ],
        ];
    }
}
