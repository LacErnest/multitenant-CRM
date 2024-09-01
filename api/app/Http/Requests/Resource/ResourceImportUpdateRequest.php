<?php

namespace App\Http\Requests\Resource;

use Illuminate\Foundation\Http\FormRequest;

class ResourceImportUpdateRequest extends FormRequest
{

    protected string $existingResourceId;

    public function __construct(string $existingResourceId)
    {
        $this->existingResourceId = $existingResourceId;
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
              'unique:tenant.resources,email,' . $this->existingResourceId,
              'nullable',
          ],
          'tax_number' => [
              'string',
              'max:128',
              'nullable',
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
        ];
    }
}
