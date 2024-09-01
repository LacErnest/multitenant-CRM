<?php

namespace App\Http\Requests\Employee;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;


class EmployeeGetRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isSales = UserRole::isSales(auth()->user()->role);
        $isRestrictedPM = UserRole::isPm_restricted(auth()->user()->role);

        return !$isSales && !$isRestrictedPM;
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
