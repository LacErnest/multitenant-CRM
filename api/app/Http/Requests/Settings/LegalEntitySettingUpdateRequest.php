<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\BaseRequest;

class LegalEntitySettingUpdateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'quote_number' => [
              'required',
              'string',
              'max:128',
          ],
          'quote_number_format' => [
              'required',
              'string',
              'max:128',
          ],
          'order_number' => [
              'required',
              'string',
              'max:128',
          ],
          'order_number_format' => [
              'required',
              'string',
              'max:128',
          ],
          'invoice_number' => [
              'required',
              'string',
              'max:128',
          ],
          'invoice_number_format' => [
              'required',
              'string',
              'max:128',
          ],
          'purchase_order_number' => [
              'required',
              'string',
              'max:128',
          ],
          'purchase_order_number_format' => [
              'required',
              'string',
              'max:128',
          ],
          'resource_invoice_number' => [
              'nullable',
              'string',
              'max:128',
          ],
          'resource_invoice_number_format' => [
              'nullable',
              'string',
              'max:128',
          ],
          'invoice_payment_number' => [
              'nullable',
              'string',
              'max:128',
          ],
          'invoice_payment_number_format' => [
              'nullable',
              'string',
              'max:128',
          ],
        ];
    }
}
