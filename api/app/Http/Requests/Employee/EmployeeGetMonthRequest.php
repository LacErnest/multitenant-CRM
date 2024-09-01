<?php

namespace App\Http\Requests\Employee;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class EmployeeGetMonthRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isSales(auth()->user()->role)) {
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
          'month' => [
              'integer',
              'required',
              'between:1,12',
          ],
          'year' => [
              'required',
              'integer',
          ],
          'amount' => [
              'integer',
              'min:1',
              'max:100',
          ],
          'page' => [
              'integer',
              'nullable',
              'min:0',
          ],
        ];
    }
}
