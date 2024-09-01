<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\BaseRequest;

class SmtpSettingUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'smtp_host'=>[
              'required',
              'string',
              'max:50'
          ],
          'smtp_port'=>[
              'required',
              'max:10'
          ],
          'smtp_username'=>[
              'required',
          ],
          'smtp_password'=>[
              'nullable',
              'string',
              'max:50'
          ],
          'smtp_encryption'=>[
              'required',
              'string',
              'max:50'
          ],
          'sender_email'=>[
              'required',
              'email',
          ],
          'sender_name'=>[
              'required',
              'string',
              'max:200',
          ],
        ];
    }
}
