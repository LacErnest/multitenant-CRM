<?php

namespace App\Http\Requests\Employee;

class EmployeeUpdateRequest extends EmployeeCreateRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
          'email' => [
              'email',
              'max:128',
              'unique:tenant.employees,email,' . $this->employee_id,
              'nullable',
          ],
        ]);
    }
}
