<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\BaseRequest;

class CompanyNotificationSettingCreateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'from_address'=>[
              'required',
              'email'
          ],
          'from_name'=>[
              'required',
              'max:50'
          ],
          'cc_addresses' => [
              'required',
              'array',
          ],
          'cc_addresses.*' => [
              'required',
              'email',
          ],
          'invoice_submitted_body' => [
              'required',
              'string',
              'max:20000',
          ],
        ];
    }
}
