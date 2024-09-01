<?php

namespace App\Http\Requests\Loan;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class CreateCompanyLoanRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return UserRole::isAdmin(auth()->user()->role);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'issued_at' => [
              'date_format:Y-m-d',
              'required',
          ],
          'amount' => [
              'numeric',
              'required',
              'min:1',
              'max:1000000000',
          ],
          'description' => [
              'nullable',
              'string',
              'max:255',
          ],
        ];
    }
}
