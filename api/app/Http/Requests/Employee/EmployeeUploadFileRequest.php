<?php

namespace App\Http\Requests\Employee;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class EmployeeUploadFileRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isSales = UserRole::isSales(auth()->user()->role);
        $readOwner = UserRole::isOwner_read(auth()->user()->role);
        $isRestrictedPM = UserRole::isPm_restricted(auth()->user()->role);

        return !$isSales && !$readOwner && !$isRestrictedPM;
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
              'base64mimes:pdf,docx,PDF,DOCX,doc,DOC',
              'base64max:51200',
          ],
          'file_name' => [
              'required',
              'string',
              'max:128',
          ],
        ];
    }
}
