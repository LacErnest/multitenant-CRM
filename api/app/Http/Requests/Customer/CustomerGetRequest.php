<?php

namespace App\Http\Requests\Customer;

use App\Enums\CurrencyCode;
use App\Enums\CustomerStatus;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;


class CustomerGetRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isPm(auth()->user()->role) || UserRole::isHr(auth()->user()->role) || UserRole::isPm_restricted(auth()->user()->role)) {
            return false;
        }
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

        ];
    }
}
