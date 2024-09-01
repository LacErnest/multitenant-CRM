<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class UserSuggestForAllCompaniesRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isAdmin(auth()->user()->role) || UserRole::isOwner(auth()->user()->role) ||
          UserRole::isSales(auth()->user()->role) || UserRole::isAccountant(auth()->user()->role) ||
          UserRole::isOwner_read(auth()->user()->role)) {
            return true;
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'value' => 'string|max:500|min:1'
        ];
    }
}
