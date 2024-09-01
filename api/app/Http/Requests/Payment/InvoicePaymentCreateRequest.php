<?php

namespace App\Http\Requests\Payment;

use App\Enums\CurrencyCode;
use App\Http\Requests\BaseRequest;
use App\Models\InvoicePayment;
use App\Rules\InvoicePaymentAmountValidate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InvoicePaymentCreateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return [
          'currency_code' => [
              'integer',
              'required',
              'enum:' . CurrencyCode::class,
          ],
          'pay_date' => [
              'required',
              'date'
          ],
          'pay_amount' => [
              'required',
              'numeric',
              new InvoicePaymentAmountValidate($this->invoice_id)
          ],
          'pay_full_price'=>[
              'nullable',
          ],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $this->checkEuropeanTaxNumber($validator, InvoicePayment::class);
    }

    /**
     * Handle a passed validation attempt.
     *
     * @return void
     */
    protected function passedValidation()
    {
        $this->merge(
            [
              'pay_amount' => floatval($this->input('pay_amount')),
              'pay_full_price' => !empty($this->input('pay_full_price'))
            ]
        );
    }

    /**
     * Handle validated attempt.
     *
     * @return array
     */
    public function validated(): array
    {
        return array_merge(parent::validated(), [
          'pay_amount' => floatval($this->input('pay_amount')),
          'pay_full_price' => !empty($this->input('pay_full_price'))
        ]);
    }
}
