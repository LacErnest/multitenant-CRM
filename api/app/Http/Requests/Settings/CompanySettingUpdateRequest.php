<?php

namespace App\Http\Requests\Settings;

use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;

class CompanySettingUpdateRequest extends BaseRequest
{
    public function authorize()
    {
        if (!auth()->user()->super_user) {
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
          'sales_person_commission_limit' => [
              'nullable',
              'integer',
              'min:1',
              'max:10',
          ],
          'max_commission_percentage' => [
              'nullable',
              'numeric',
              'between:0,100',
          ],
          'vat_default_value' => [
              'nullable',
              'numeric',
              'between:0,100',
          ],
          'vat_max_value' => [
              'nullable',
              'numeric',
              'between:0,100',
          ],
          'project_management_default_value' => [
              'nullable',
              'numeric',
              'between:0,100',
          ],
          'project_management_max_value' => [
              'nullable',
              'numeric',
              'between:0,100',
          ],
          'special_discount_default_value' => [
              'nullable',
              'numeric',
              'between:0,100',
          ],
          'special_discount_max_value' => [
              'nullable',
              'numeric',
              'between:0,100',
          ],
          'director_fee_default_value' => [
              'nullable',
              'numeric',
              'between:0,100',
          ],
          'director_fee_max_value' => [
              'nullable',
              'numeric',
              'between:0,100',
          ],
          'transaction_fee_default_value' => [
              'nullable',
              'numeric',
              'between:0,100',
          ],
          'transaction_fee_max_value' => [
              'nullable',
              'numeric',
              'between:0,100',
          ],
        ];
    }
}
