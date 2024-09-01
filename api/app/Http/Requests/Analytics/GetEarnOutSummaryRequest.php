<?php

namespace App\Http\Requests\Analytics;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class GetEarnOutSummaryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isAdmin = UserRole::isAdmin(auth()->user()->role);
        $isOwner = UserRole::isOwner(auth()->user()->role);
        $isAccountant = UserRole::isAccountant(auth()->user()->role);
        $readOwner = UserRole::isOwner_read(auth()->user()->role);

        return $isAdmin || $isOwner || $isAccountant || $readOwner;
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
              'integer',
              'nullable',
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
