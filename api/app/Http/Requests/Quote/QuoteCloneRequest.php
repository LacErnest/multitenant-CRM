<?php

namespace App\Http\Requests\Quote;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class QuoteCloneRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isPm(auth()->user()->role) || UserRole::isHr(auth()->user()->role) ||
          UserRole::isOwner_read(auth()->user()->role) || UserRole::isPm_restricted(auth()->user()->role)) {
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
          'destination_id' => 'string|nullable|max:36'
        ];
    }
}
