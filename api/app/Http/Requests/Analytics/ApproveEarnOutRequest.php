<?php

namespace App\Http\Requests\Analytics;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class ApproveEarnOutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return UserRole::isOwner(auth()->user()->role);
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
