<?php

namespace App\Http\Requests\EmployeeHistory;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeHistoryDeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $userRole = auth()->user()->role;
        if (UserRole::isAdmin($userRole) || UserRole::isOwner($userRole) || UserRole::isHr($userRole)
          || UserRole::isAccountant($userRole)) {
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
          //
        ];
    }
}
