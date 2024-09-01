<?php

namespace App\Http\Requests\Project;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\Project;

class AssignEmployeeRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isSales(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role)) {
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
            'hours' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999',
            ],
            'month' => [
                'required',
                'string',
                'max:9',
            ],
            'year' => [
                'required',
                'integer',
                'min:' . (now()->year - 2),
                'max:' . (now()->year + 1),
            ],
        ];
    }
}
