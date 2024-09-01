<?php

namespace App\Http\Requests\Service;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class ServiceDeleteRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $userRole = auth()->user()->role;
        $isOwner = UserRole::isOwner($userRole);
        $isAdmin = UserRole::isAdmin($userRole);
        $isAccountant = UserRole::isAccountant($userRole);

        return $isOwner || $isAdmin || $isAccountant;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          '*' => 'exists:tenant.services,id'
        ];
    }
}
