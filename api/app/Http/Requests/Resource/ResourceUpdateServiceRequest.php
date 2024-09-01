<?php

namespace App\Http\Requests\Resource;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class ResourceUpdateServiceRequest extends BaseRequest
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
          'name' => [
              'required',
              'string',
              'max:255',
          ],
          'price' => [
              'required',
              'numeric',
              'regex:/^\d*(\.\d{1,2})?$/',
          ],
          'price_unit' => [
              'required',
              'string',
              'max:25',
          ],
        ];
    }
}
