<?php

namespace App\Http\Requests\TaxRate;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;

class CreateTaxRateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
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
          'tax_rate' => [
              'required',
              'numeric',
              'between:0,100',
          ],
          'start_date' => [
              'date',
              'required',
          ],
          'end_date' => [
              'date',
              'nullable',
              'after:start_date',
          ],
          'xero_sales_tax_type' => [
              'string',
              'nullable',
              'max:20',
          ],
          'xero_purchase_tax_type' => [
              'string',
              'nullable',
              'max:20',
          ],
        ];
    }
}
