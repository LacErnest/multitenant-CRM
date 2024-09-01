<?php

namespace App\Http\Requests\Order;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class OrderAddDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isSales(auth()->user()->role) || UserRole::isHr(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role)) {
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
          'file' => [
              'required',
              'base64file',
              'base64mimes:docx,DOCX,doc,DOC',
              'base64max:51200',
          ],
        ];
    }
}
