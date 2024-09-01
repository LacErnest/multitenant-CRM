<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class GetEarnOutStatusRequest extends FormRequest
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
        $isOwner = UserRole::isOwner(auth()->user()->role);
        $isReadOnly = UserRole::isOwner_read(auth()->user()->role);

        return $isAdmin || $isAccountant || $isOwner || $isReadOnly;
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
              'integer',
              'max:' . now()->year,
          ],
        ];
    }
}
