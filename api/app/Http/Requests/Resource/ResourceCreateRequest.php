<?php

namespace App\Http\Requests\Resource;

use App\Enums\Country;
use App\Enums\CurrencyCode;
use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use App\Http\Requests\BaseRequest;
use App\Models\Resource;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;


class ResourceCreateRequest extends BaseRequest
{
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
          'addressline_1' => [
              'string',
              'max:128',
              'nullable',
          ],
          'addressline_2' => [
              'string',
              'max:128',
              'nullable',
          ],
          'city' => [
              'string',
              'max:128',
              'nullable',
          ],
          'region' => [
              'string',
              'max:128',
              'nullable',
          ],
          'postal_code' => [
              'string',
              'max:128',
              'nullable',
          ],
          'country' => [
              'required',
              'numeric',
              'nullable',
              'enum:' . Country::class,
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
          'contract_file' => [
              'nullable',
              'base64file',
              'base64mimes:pdf,docx,PDF,DOCX,doc,DOC',
              'base64max:51200',
          ],
          'legal_entity_id' => [
              'required',
              'uuid',
              Rule::exists('company_legal_entity', 'legal_entity_id')
                  ->where('company_id', $this->route('company_id')),
          ],
          'non_vat_liable' => 'boolean',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $this->checkEuropeanTaxNumber($validator, Resource::class);
    }
}
