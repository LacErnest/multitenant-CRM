<?php


namespace App\Repositories;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoicePayment;


/**
 * Class InvoicePaymentRepository
 *
 * @deprecated
 */
class InvoicePaymentRepository
{
    /**
     * @var InvoicePayment
     */
    protected InvoicePayment $invoicePayment;

    public function __construct(InvoicePayment $invoicePayment)
    {
        $this->invoicePayment = $invoicePayment;
    }

    public function create($invoice_id, $attributes)
    {
        $invoice = Invoice::with('order')->findOrFail($invoice_id);

        $attributes['invoice_id'] = $invoice->id;
        $attributes['created_by'] = auth()->user()->id;
        $invoicePayment = $this->invoicePayment->create($attributes);
        return $invoicePayment;
    }

    public function update(InvoicePayment $invoicePayment, $attributes)
    {
        if (array_key_exists('pay_date', $attributes) && array_key_exists('status', $attributes)) {
            if (!InvoiceStatus::isPaid($attributes['status'])) {
                unset($attributes['pay_date']);
            }
        }

        $invoicePayment->update($attributes);

        return $invoicePayment->refresh();
    }
}
