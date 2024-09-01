<?php

namespace App\Http\Requests\Quote;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;


class QuoteGetRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isHr = UserRole::isHr(auth()->user()->role);

        return !$isHr;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }
}
