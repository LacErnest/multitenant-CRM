<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class PurchaseOrderCloneRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isNotSP = !UserRole::isSales(auth()->user()->role);
        $isNotHR = !UserRole::isHr(auth()->user()->role);
        $isNotReadOnly = !UserRole::isOwner_read(auth()->user()->role);

        return $isNotHR && $isNotSP && $isNotReadOnly;
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
