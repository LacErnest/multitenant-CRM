<?php

namespace App\Http\Requests\Resource;

use App\Enums\UserRole;
use App\Models\Resource;

class ResourceUpdateRequest extends ResourceCreateRequest
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
            return UserRole::isOwner(auth()->user()->role) || UserRole::isAccountant(auth()->user()->role) ||
            UserRole::isAdmin(auth()->user()->role) || UserRole::isPm(auth()->user()->role) ||
            UserRole::isHr(auth()->user()->role) || UserRole::isPm_restricted(auth()->user()->role);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $parentRules = parent::rules();
        if ((auth()->user() instanceof Resource)) {
            $parentRules['legal_entity_id'] = array_diff($parentRules['legal_entity_id'], ['required']);
        }

        return array_merge($parentRules, [
          'email' => [
              'email',
              'max:128',
              'unique:tenant.resources,email,' . $this->resource_id,
              'nullable',
          ],
        ]);
    }
}
