<?php

namespace App\Http\Requests\Project;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteEmployeeRequest extends FormRequest
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
          '*' => [
              Rule::exists('tenant.project_employees', 'employee_id')
                  ->where('project_id', $this->route('project_id')),
          ],
        ];
    }
}
