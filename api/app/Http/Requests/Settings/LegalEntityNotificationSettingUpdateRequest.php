<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\BaseRequest;

class LegalEntityNotificationSettingUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'enable_submited_invoice_notification' => [
              'required',
              'boolean',
          ],
          'notification_contacts' => [
              'required_if:enable_submited_invoice_notification,true'
          ],
          'notification_footer' => [
              'required_if:enable_submited_invoice_notification,true',
              'string',
              'max:2000',
          ],
        ];
    }
}
