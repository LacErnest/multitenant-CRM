<?php

namespace App\Http\Requests\Payment;

use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Requests\BaseRequest;
use App\Models\Invoice;
use App\Rules\InvoicePaymentAmountValidate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InvoicePaymentUpdateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isOwner = UserRole::isOwner(auth()->user()->role);
        $isAdmin = UserRole::isAdmin(auth()->user()->role);
        $isAccountant = UserRole::isAccountant(auth()->user()->role);

        return $isOwner || $isAdmin || $isAccountant;
    }

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
              new InvoicePaymentAmountValidate($this->invoice_id, $this->invoice_payment_id)
          ]
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $invoice_id = $this->route('invoice_id');
            $invoice = Invoice::findOrFail($invoice_id);

            if ($invoice->shadow) {
                $validator->errors()->add('status', 'This invoice is a shadow. No updates allowed.');
                return;
            }

            if ($invoice->status === InvoiceStatus::cancelled()->getIndex()) {
                $validator->errors()->add('status', 'Invoices with the status Cancelled can not be updated.');
                return;
            }
            if (!($this->status === null)) {
                if ($this->status != InvoiceStatus::cancelled()->getIndex() && $invoice->status === InvoiceStatus::cancelled()->getIndex()) {
                    $validator->errors()->add('status', 'The status Cancelled can not be updated to another status.');
                }
                if ($this->status == InvoiceStatus::cancelled()->getIndex()) {
                    if (!UserRole::isAdmin(auth()->user()->role)) {
                        $validator->errors()->add('status', 'Only users with an admin role can update the status to Cancelled.');
                    }
                }
            }
        });
    }

    /**
     * Handle a passed validation attempt.
     *
     * @return void
     */
    protected function passedValidation()
    {
        $this->merge(
            ['pay_amount' => floatval($this->input('pay_amount'))]
        );
    }

    /**
     * Handle validated attempt.
     *
     * @return array
     */
    public function validated(): array
    {
        return array_merge(parent::validated(), ['pay_amount' => floatval($this->input('pay_amount'))]);
    }
}
