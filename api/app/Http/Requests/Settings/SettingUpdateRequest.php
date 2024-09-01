<?php

namespace App\Http\Requests\Settings;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\UnauthorizedException;

class SettingUpdateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isOwner(auth()->user()->role) || UserRole::isAccountant(auth()->user()->role) || UserRole::isAdmin(auth()->user()->role)) {
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
          'quote_number' => 'required|string|nullable',
          'quote_number_format' => 'required|string|max:255|nullable',
          'order_number' => 'required|string|max:255|nullable',
          'order_number_format' => 'required|string|max:255|nullable',
          'invoice_number' => 'required|string|max:255|nullable',
          'invoice_number_format' => 'required|string|max:255|nullable',
          'purchase_order_number' => 'required|string|max:255|nullable',
          'purchase_order_number_format' => 'required|string|max:255|nullable',
          'resource_invoice_number' => 'string|max:255|nullable',
          'resource_invoice_number_format' => 'string|max:255|nullable',
        ];
    }
}
