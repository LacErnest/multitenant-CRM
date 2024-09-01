<?php

namespace App\Http\Requests\Commission;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndividualCommissionPaymentFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isAccountant(auth()->user()->role) || UserRole::isAdmin(auth()->user()->role) || UserRole::isOwner(auth()->user()->role)) {
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
              'required',
              'uuid'
          ],
          'amount' => [
              'required',
              'numeric',
              'min:0.01',
              'max:1000000000',
          ],
          'order_id' => [
              'required',
              'uuid',
          ],
          'invoice_id' => [
              'required',
              'uuid',
          ],
          'total' => [
              'required',
              'numeric',
              'min:0.01',
              'max:1000000000',
          ],
        ];
    }
}
