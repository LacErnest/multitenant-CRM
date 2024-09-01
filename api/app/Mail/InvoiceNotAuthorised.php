<?php

namespace App\Mail;

use App\Mail\Traits\InvoiceMailableTrait;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\SmtpSetting;
use App\Notifications\Mailable\TenancyMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use Tenancy\Facades\Tenancy;

class InvoiceNotAuthorised extends TenancyMailable
{
    use Queueable, SerializesModels, InvoiceMailableTrait;

    public $tenant_key;
    protected $invoice_id;

    /**
     * Create a new message instance.
     *
     * @param $tenant_key
     * @param $quote_id
     */
    public function __construct($tenant_key, $invoice_id)
    {
        $this->tenant_key = $tenant_key;
        $this->invoice_id = $invoice_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $company = Company::find($this->tenant_key);
        Tenancy::setTenant($company);
        $invoice = Invoice::find($this->invoice_id);

        if ($invoice) {
            return $this->subject('Invoice ' . $invoice->number . ' waiting for approval')
                ->markdown('emails.invoice_not_authorised_mail')
                ->with([
                    'invoiceNumber' => $invoice->number,
                    'url' => config('app.front_url') . '/' . $company->id . '/projects/' . $invoice->project_id . '/invoices/' . $invoice->id . '/edit',
                ]);
        } else {
            throw new InvalidArgumentException('Invoice not found');
        }
    }
}
