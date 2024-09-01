<?php

namespace App\Http\Requests\Rent;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class getCompanyRentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!is_integer(auth()->user()->role)) {
            return false;
        }

        $isAdmin = UserRole::isAdmin(auth()->user()->role);
        $isOwner = UserRole::isOwner(auth()->user()->role);
        $isAccountant = UserRole::isAccountant(auth()->user()->role);
        $isReadOnly = UserRole::isOwner_read(auth()->user()->role);

        return $isAdmin || $isOwner || $isAccountant ||$isReadOnly;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'amount' => [
              'integer',
              'min:1',
              'max:100',
          ],
          'page' => [
              'integer',
              'nullable',
              'min:0',
          ],
        ];
    }
}
