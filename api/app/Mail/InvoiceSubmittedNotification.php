<?php

namespace App\Mail;

use App\Events\InvoiceSubmittedSuccessfully;
use App\Mail\Traits\InvoiceMailableTrait;
use App\Models\Company;
use App\Models\Invoice;
use App\Notifications\Mailable\TenancyMailable;
use App\Services\EmailTemplateService;
use App\Utils\EmailUtils;
use App\Utils\ImageUtils;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Tenancy\Facades\Tenancy;

class InvoiceSubmittedNotification extends TenancyMailable
{
    use Queueable, SerializesModels, InvoiceMailableTrait;

    public $tenant_key;
    protected $invoice_id;
    /**
     * @var \App\Services\EmailTemplateService
     */
    private $emailTemplateService;
    protected $emailUtil;

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
        $this->emailTemplateService = app(EmailTemplateService::class);
        $this->emailUtil = new EmailUtils();
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
        $invoice = Invoice::findOrFail($this->invoice_id);

        $emailTemplate = EmailUtils::getEntityEmailTemplate($invoice);

        $this->emailUtil->loadVariables($company, $invoice);

        return $this->addSubject($invoice, $emailTemplate)
            ->addBody($company, $invoice, $emailTemplate->designTemplate ?? null)
            ->with([
                'invoiceNumber' => $invoice->number,
            ]);
        ;
    }


    /**
     * Send the message using the given mailer.
     *
     * @param  \Illuminate\Contracts\Mail\Factory|\Illuminate\Contracts\Mail\Mailer  $mailer
     * @return void
     */
    public function send($mailer)
    {
        // Send email and capture the result
        parent::send($mailer);
    }

    protected function getEventOnSuccess(): ?string
    {
        return InvoiceSubmittedSuccessfully::class;
    }

    /**
     * @param $reminder
     */
    private function addSubject(Invoice $invoice, $emailTemplate = null): self
    {

        $subject = $emailTemplate->designTemplate->subject ?? 'Invoice ' . $invoice->number . ' has been submitted';

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
            return $this->markdown('emails.invoice_submitted_mail');
        } else {
            $body = $this->emailUtil->buildEmailTemplate($company, $invoice, $designTemplate);
            $body = ImageUtils::replaceImageUris($body);
            return $this->html($body);
        }
    }
}
