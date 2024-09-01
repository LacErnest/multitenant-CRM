<?php

namespace App\Http\Requests\Invoice;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class InvoiceCloneRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isOwner = UserRole::isOwner(auth()->user()->role);
        $isAdmin = UserRole::isAdmin(auth()->user()->role);
        $isAccountant = UserRole::isAccountant(auth()->user()->role);

        return $isOwner || $isAdmin || $isAccountant;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'destination_id' => 'string|required|max:36'
        ];
    }
}
