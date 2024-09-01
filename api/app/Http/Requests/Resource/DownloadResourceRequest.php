<?php

namespace App\Http\Requests\Resource;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class DownloadResourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isSales = UserRole::isSales(auth()->user()->role);

        return !$isSales;
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
