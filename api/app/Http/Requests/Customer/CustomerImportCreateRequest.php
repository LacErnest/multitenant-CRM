<?php

namespace App\Http\Requests\Customer;

use App\Enums\CurrencyCode;
use App\Enums\CustomerStatus;
use App\Enums\IndustryType;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerImportCreateRequest extends FormRequest
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
          'name' => 'required|string|max:128|nullable',
          'email' => 'email|max:128|unique:customers,email|nullable',
          'status' => 'nullable|integer|enum:' . CustomerStatus::class,
          'description' => 'string|nullable|max:500',
          'industry' => 'integer|nullable|enum:' . IndustryType::class,
          'tax_number' => 'string|max:128|nullable',
          'default_currency' => 'numeric|required|enum:' . CurrencyCode::class,
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
