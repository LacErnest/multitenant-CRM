<?php

namespace App\Http\Requests\Resource;

use App\Enums\UserRole;
use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;

class ResourceGetServicesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ((auth()->user() instanceof Resource)) {
            return $this->route('resource_id') === auth()->user()->id;
        } else {
            return UserRole::isOwner(auth()->user()->role) ||
                 UserRole::isAccountant(auth()->user()->role) ||
                 UserRole::isAdmin(auth()->user()->role) ||
                 UserRole::isPm(auth()->user()->role) ||
                 UserRole::isHr(auth()->user()->role) ||
                 UserRole::isOwner_read(auth()->user()->role) ||
                 UserRole::isPm_restricted(auth()->user()->role);
        }
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
