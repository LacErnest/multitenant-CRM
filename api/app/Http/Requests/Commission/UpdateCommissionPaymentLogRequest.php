<?php

namespace App\Http\Requests\Commission;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateCommissionPaymentLogRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isAccountant(auth()->user()->role) || UserRole::isAdmin(auth()->user()->role)) {
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
          'sales_person_id' => [
              'nullable',
              Rule::exists('users', 'id')
                  ->whereIn('role', [UserRole::sales()->getIndex(), UserRole::owner()->getIndex(), UserRole::admin()->getIndex()]),
          ],
          'amount' => [
              'numeric',
              'min:0.01',
              'max:1000000000',
          ],
          'approved' => [
              'boolean'
          ]
        ];
    }
}
