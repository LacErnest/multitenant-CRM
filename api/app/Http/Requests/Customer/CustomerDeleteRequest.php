<?php

namespace App\Http\Requests\Customer;

use App\Enums\CurrencyCode;
use App\Enums\CustomerStatus;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;


class CustomerDeleteRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isOwner(auth()->user()->role) || UserRole::isAccountant(auth()->user()->role) || UserRole::isAdmin(auth()->user()->role)) {
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
          '*' => 'exists:tenant.customers,id',
        ];
    }
}
