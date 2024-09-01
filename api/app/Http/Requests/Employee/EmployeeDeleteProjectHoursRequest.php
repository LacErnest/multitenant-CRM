<?php

namespace App\Http\Requests\Employee;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class EmployeeDeleteProjectHoursRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isSales(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role)) {
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
          'employee_id' => [
              'required',
              'uuid',
              'exists:tenant.employees,id',
          ],
          'order_id' => [
              'required',
              'uuid',
              'exists:tenant.orders,id',
          ],
          'month' => [
              'required',
              'date_format:Y-m',
          ],
        ];
    }
}
