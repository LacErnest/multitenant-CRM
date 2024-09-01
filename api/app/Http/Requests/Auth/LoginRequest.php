<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
        return
          [
              'email' => 'required|email|max:256',
              'password' => 'required|string|between:8,256',
              'token' => 'string|digits:6',
              'trust_device' => 'bool',
          ];
    }
}
