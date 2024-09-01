<?php

namespace App\Http\Requests\TaxRate;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class DeleteTaxRateRequest extends FormRequest
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

        return $isAdmin || $isOwner || $isAccountant;
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
