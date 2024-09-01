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

class InvoiceAuthorised extends TenancyMailable
{
    use Queueable, SerializesModels, InvoiceMailableTrait;

    public $tenant_key;
    protected $invoice_id;

    /**
     * Create a new message instance.
     *
     * @param $tenant_key
     * @param $invoice_id
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
            return $this->subject('Invoice '.$invoice->number.' ready to be submitted')
            ->markdown('emails.invoice_authorised_mail')
            ->with([
                'invoiceNumber' => $invoice->number,
                'url' => config('app.front_url').'/'.$company->id.'/projects/'.$invoice->project_id.'/invoices/'.$invoice->id.'/edit',
            ]);
        } else {
            throw new InvalidArgumentException('Invoice not found');
        }
    }

    /**
     * Get smtp settings from witch email must be sent
     * @return SmtpSetting | null
     */
    protected function getSmtpSettings()
    {
        $company = Company::find($this->tenant_key);
        if ($company) {
            Tenancy::setTenant($company);
            $invoice = Invoice::find($this->invoice_id);
            return $invoice->emailTemplate->smtpSetting ?? null;
        }
        return null;
    }

    /**
     * Get entity id for cookies setting
     * @return string
     */
    protected function getEntityKey(): string
    {
        return 'Invoice-Id';
    }

    /**
     * Get entity id for cookies setting
     * @return string
     */
    protected function getEntityId(): string
    {
        return $this->invoice_id;
    }

    /**
     * Get tenant id for cookies setting
     * @return string
     */
    protected function getTenantId(): string
    {
        return $this->tenant_key;
    }
}
