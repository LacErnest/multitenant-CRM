<?php

namespace App\Http\Requests\User;

use App\Enums\CommissionModel;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Validator;

class UserCreateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isOwner(auth()->user()->role) || UserRole::isAdmin(auth()->user()->role)) {
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
          'first_name' => 'required|string|max:128',
          'last_name' => 'required|string|max:128',
          'email' => 'required|email|max:256',
          'role' => 'required|integer|enum:' . UserRole::class,
          'commission_percentage' => 'numeric|min:0|max:100|nullable',
          'second_sale_commission' => 'numeric|min:0|max:100|nullable',
          'commission_model' => 'nullable|integer|enum:' . CommissionModel::class,
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->role === UserRole::admin()->getIndex() && !UserRole::isAdmin(auth()->user()->role)) {
                $validator->errors()->add('role', 'You are not allowed to create users with this role.');
            }
        });
    }
}
