<?php

namespace App\Http\Requests\Rent;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class createCompanyRentRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!is_integer(auth()->user()->role)) {
            return false;
        }

        $isAdmin = UserRole::isAdmin(auth()->user()->role);
        $isOwner = UserRole::isOwner(auth()->user()->role);
        $isAccountant = UserRole::isAccountant(auth()->user()->role);

        return $isAdmin || $isOwner || $isAccountant;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'start_date' => [
              'date_format:Y-m-d',
              'required',
          ],
          'amount' => [
              'numeric',
              'required',
              'min:1',
              'max:1000000',
          ],
          'name' => [
              'string',
              'nullable',
              'max:50',
          ],
          'end_date' => [
              'date_format:Y-m-d',
              'nullable',
          ],
        ];
    }
}
