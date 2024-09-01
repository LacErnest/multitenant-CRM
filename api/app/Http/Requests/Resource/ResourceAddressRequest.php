<?php

namespace App\Http\Requests\Resource;

use App\Enums\Country;
use Illuminate\Foundation\Http\FormRequest;

class ResourceAddressRequest extends FormRequest
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
        ];
    }
}
