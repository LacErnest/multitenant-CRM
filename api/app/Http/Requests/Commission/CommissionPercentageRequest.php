<?php

namespace App\Http\Requests\Commission;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Rules\AllowedSalePersonRule;
use Illuminate\Validation\Rule;

class CommissionPercentageRequest extends BaseRequest
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
          'commission_percentage' => [
              'required',
              'numeric',
          ],
          'sales_person_id'=> [new AllowedSalePersonRule($this->company_id, $this->route('orderId'), $this->route('invoiceId'))],
        ];
    }
}
