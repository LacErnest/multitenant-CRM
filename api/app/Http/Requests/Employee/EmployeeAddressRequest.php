<?php

namespace App\Http\Requests\Employee;

use App\Enums\Country;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeAddressRequest extends FormRequest
{
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
              'numeric',
              'enum:' . Country::class,
          ],
        ];
    }
}
