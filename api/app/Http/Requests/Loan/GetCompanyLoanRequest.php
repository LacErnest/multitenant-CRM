<?php

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class GetCompanyLoanRequest extends FormRequest
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
          'amount' => [
              'integer',
              'min:1',
              'max:100',
          ],
          'page' => [
              'integer',
              'nullable',
              'min:0',
          ],
        ];
    }
}
