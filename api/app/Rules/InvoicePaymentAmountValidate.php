<?php

namespace App\Rules;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Contracts\Validation\Rule;

class InvoicePaymentAmountValidate implements Rule
{

    private $invoiceId;
    private $invoicePaymentId;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(string $invoiceId, ?string $invoicePaymentId = null)
    {
        $this->invoiceId = $invoiceId;
        $this->invoicePaymentId = $invoicePaymentId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {

        $invoice = Invoice::findOrFail($this->invoiceId);
        $total_paid_amount = $invoice->payments->sum('pay_amount');
        $invoiceTotalPrice = get_total_price(Invoice::class, $invoice->id);
        // If it is an update payment, we remove old payment amount
        if ($this->invoicePaymentId && $invoicePayment = InvoicePayment::find($this->invoicePaymentId)) {
            $total_paid_amount -= $invoicePayment->pay_amount;
        }

        if (round($invoiceTotalPrice) < round($total_paid_amount + $value)) {
            return false;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The total paid amount cannont been greater than invoice total amount.';
    }
}
