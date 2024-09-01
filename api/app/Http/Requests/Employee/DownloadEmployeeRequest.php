<?php

namespace App\Http\Requests\Employee;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class DownloadEmployeeRequest extends FormRequest
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
        $isHr = UserRole::isHr(auth()->user()->role);
        $readOwner = UserRole::isOwner_read(auth()->user()->role);

        return $isHr || $isAdmin || $isOwner || $readOwner;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          //
        ];
    }
}
