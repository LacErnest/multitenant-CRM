<?php

namespace App\Http\Requests\Import;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class FinalizeImportFileRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isOwner = UserRole::isOwner(auth()->user()->role);
        $isAccountant = UserRole::isAccountant(auth()->user()->role);
        $isAdmin = UserRole::isAdmin(auth()->user()->role);

        return ($isOwner || $isAccountant || $isAdmin);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'id' => 'required|uuid',
          'matches' => 'required|array',
          'matches.*.property' => 'required|string',
          'matches.*.column' => 'required|string',
        ];
    }
}
