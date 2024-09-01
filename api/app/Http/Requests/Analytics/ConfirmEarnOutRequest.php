<?php

namespace App\Http\Requests\Analytics;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmEarnOutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isAdmin = UserRole::isAdmin(auth()->user()->role);
        $isAccountant = UserRole::isAccountant(auth()->user()->role);

        return $isAdmin || $isAccountant;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'quarter' => [
              'required',
              'integer',
              'between:1,4',
          ],
          'year' => [
              'required',
              'integer',
              'max:' . now()->year,
          ],
        ];
    }
}
