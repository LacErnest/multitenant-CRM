<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;

class UpdateMailPreferencesRequest extends BaseRequest
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
          'customers' => 'required|boolean',
          'quotes' => 'required|boolean',
          'invoices' => 'required|boolean',
        ];
    }
}
