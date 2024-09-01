<?php

namespace App\Http\Requests\Analytics;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommissionSummaryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isPm(auth()->user()->role) || UserRole::isHr(auth()->user()->role) ) {
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
          'day' => 'integer|between:1,31',
          'week' => 'integer|between:1,52',
          'month' => 'integer|between:1,12',
          'quarter' => 'integer|between:1,4',
          'year' => 'integer|max:' . now()->year,
          'sales_person_id' => [
              'nullable',
              Rule::exists('users', 'id')
                  ->whereIn('role', [UserRole::sales()->getIndex()])
          ]
        ];
    }
}
