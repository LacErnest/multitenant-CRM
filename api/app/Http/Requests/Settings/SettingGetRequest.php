<?php

namespace App\Http\Requests\Settings;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\UnauthorizedException;

class SettingGetRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isOwner(auth()->user()->role) || UserRole::isAccountant(auth()->user()->role) ||
          UserRole::isAdmin(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role)) {
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
        return [];
    }
}
