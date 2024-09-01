<?php

namespace App\Http\Requests\Resource;

use App\Enums\CurrencyCode;
use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResourceImportCreateRequest extends FormRequest
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
          'name' => [
              'required',
              'string',
              'max:128',
          ],
          'first_name' => [
              'nullable',
              'string',
              'max:128',
          ],
          'last_name' => [
              'nullable',
              'string',
              'max:128',
          ],
          'email' => [
              'email',
              'max:128',
              'unique:tenant.resources,email',
              'nullable',
          ],
          'type' => [
              'integer',
              'nullable',
              'enum:' . ResourceType::class,
          ],
          'status' => [
              'integer',
              'nullable',
              'enum:' . ResourceStatus::class,
          ],
          'tax_number' => [
              'string',
              'max:128',
              'nullable',
          ],
          'default_currency' => [
              'numeric',
              'required',
              'enum:' . CurrencyCode::class,
          ],
          'phone_number' => [
              'string',
              'max:128',
              'nullable',
          ],
          'job_title' => [
              'string',
              'max:128',
              'nullable',
          ],
          'hourly_rate' => [
              'regex:/^\d*(\.\d{1,2})?$/',
              'nullable',
              'numeric',
              'between:0.01,999999.99',
          ],
          'daily_rate' => [
              'regex:/^\d*(\.\d{1,2})?$/',
              'nullable',
              'numeric',
              'between:0.01,999999.99',
          ],
          'legal_entity_id' => [
              'required',
              'uuid',
              Rule::exists('company_legal_entity', 'legal_entity_id')
                  ->where('company_id', $this->companyId),
          ],
        ];
    }
}
