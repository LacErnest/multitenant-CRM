<?php

namespace App\Http\Requests\Templates;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class TemplateCreateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isOwner(auth()->user()->role) || UserRole::isAccountant(auth()->user()->role) || UserRole::isAdmin(auth()->user()->role) || UserRole::isHr(auth()->user()->role)) {
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
          'name' => [
              'string',
              'nullable',
              'max:50',
          ],
        ];
    }
}
