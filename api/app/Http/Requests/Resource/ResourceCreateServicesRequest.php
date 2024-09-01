<?php

namespace App\Http\Requests\Resource;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class ResourceCreateServicesRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $userRole = auth()->user()->role;
        $isSales = UserRole::isSales($userRole);
        $isReadOnly = UserRole::isOwner_read($userRole);

        return !$isSales && !$isReadOnly;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'services' => [
              'array',
              'required'
          ],
          'services.*.name' => [
              'required',
              'distinct',
              'string',
              'max:255',
          ],
          'services.*.price' => [
              'required',
              'numeric',
              'regex:/^\d*(\.\d{1,2})?$/',
          ],
          'services.*.price_unit' => [
              'required',
              'string',
              'max:25',
          ],
        ];
    }
}
