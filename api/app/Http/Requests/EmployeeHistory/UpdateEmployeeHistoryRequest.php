<?php

namespace App\Http\Requests\EmployeeHistory;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\Employee;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeHistoryRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $userRole = auth()->user()->role;
        if (
          UserRole::isAdmin($userRole) || UserRole::isOwner($userRole) || UserRole::isHr($userRole)
          || UserRole::isAccountant($userRole)
        ) {
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
          'employee_salary'        => [
              'required',
              'numeric',
              'regex:/^\d*(\.\d{1,2})?$/',
              'between:0.01,999999.99',
          ],
          'working_hours' => [
              'integer',
              'nullable',
              'min:1',
              'max:450',
              'required_if:type,1',
          ],
          'start_date' => [
              'date',
              'required',
          ],
          'end_date' => [
              'date',
              'nullable',
              'after:start_date',
          ],
        ];
    }

    /**
     * Prepare the validator for the request.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    protected function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $startDate = $this->get('start_date');
            $endDate = $this->get('end_date');
            $employeeId = $this->route('employee_id');

            $histories = Employee::findOrFail($employeeId)->histories()
              ->where(function ($q) use ($startDate, $endDate) {
                  $q->where(function ($subquery) use ($startDate, $endDate) {
                      $subquery->where(function ($q) use ($startDate) {
                          $q->where('start_date', '<=', $startDate)
                              ->where('end_date', '>=', $startDate)
                              ->whereNotNull('end_date');
                      });
                    if (!empty($endDate)) {
                        $subquery->orWhere(function ($q) use ($endDate) {
                            $q->where('start_date', '<=', $endDate)
                                ->where('end_date', '>=', $endDate)
                                ->whereNotNull('end_date');
                        });
                    }
                  })->orWhere(function ($subquery) use ($startDate, $endDate) {
                    if (empty($endDate)) {
                        $subquery->where('start_date', '>=', $startDate)
                            ->whereNull('end_date');
                    } else {
                        $subquery->whereBetween('start_date', [$startDate, $endDate])
                            ->whereNull('end_date');
                    }
                  });
              })->whereKeyNot($this->route('history_id'));

            if ($histories->exists()) {
                $validator->errors()->add('start_date', 'This record overlaps an existing record.');
            }
        });
    }
}
