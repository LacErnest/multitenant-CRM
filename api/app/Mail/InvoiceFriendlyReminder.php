<?php

namespace App\Mail;

use App\Mail\Traits\InvoiceMailableTrait;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\EmailReminder;
use App\Models\Invoice;
use App\Models\SmtpSetting;
use App\Models\Template;
use App\Notifications\Mailable\TenancyMailable;
use App\Services\Export\InvoiceExporter;
use App\Utils\EmailUtils;
use App\Utils\ImageUtils;
use App\Utils\RemindersUtils;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Tenancy\Facades\Tenancy;

class InvoiceFriendlyReminder extends TenancyMailable
{
    use Queueable, SerializesModels, InvoiceMailableTrait;

    public $tenant_key;
    protected $invoice_id;
    protected $reminder_id;
    protected $pdf;
    protected $emailUtil;
    protected $days;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($tenant_key, $invoice_id, $days, $reminder_id = null)
    {
        $this->tenant_key = $tenant_key;
        $this->invoice_id = $invoice_id;
        $this->reminder_id = $reminder_id;
        $this->emailUtil = new EmailUtils();
        $this->days = $days;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $company = Company::find($this->tenant_key);
        if ($company) {
            Tenancy::setTenant($company);

            $invoice = Invoice::find($this->invoice_id);
            if ($invoice) {
                $reminder = EmailReminder::find($this->reminder_id);
                $this->days = $reminder->value ?? $this->days;
                $this->emailUtil->loadVariables($company, $invoice, ['reminder_days' => $this->days]);
                $this->pdf = (new InvoiceExporter())->export($invoice, Template::first()->id, 'pdf');
                $designTemplate = RemindersUtils::getOverdueEmailDesign($invoice, $this->reminder_id);
                $this->addSubject($reminder)
                    ->addBody($company, $invoice, $designTemplate)
                    ->with([
                        'invoiceRef' => $invoice->reference,
                        'invoiceNumber' => $invoice->number
                    ])->attachData($this->pdf, $invoice->number . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            }
        }
    }

    /**
     * @param $reminder
     */
    private function addSubject($reminder = null): self
    {

        $subject = $reminder->designTemplate->subject ?? 'Invoice overdue by ' . $this->days . ' days';

        $subject = $this->emailUtil->translateVariable($subject);

        return $this->subject($subject);
    }

    /**
     * @param Company $company
     * @param Invoice $invoice
     * @param $designTemplate
     */
    private function addBody(Company $company, Invoice $invoice, $designTemplate = null)
    {
        if (empty($designTemplate)) {
            return $this->markdown('emails.invoice_friendly_reminder_mail');
        } else {
            $body = $this->emailUtil->buildEmailTemplate($company, $invoice, $designTemplate, ['reminder_days' => $this->days]);
            $body = ImageUtils::replaceImageUris($body);
            return $this->html($body);
        }
    }
}
