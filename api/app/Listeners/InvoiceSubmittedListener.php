<?php

namespace App\Listeners;

use App\Events\InvoiceSubmitted;
use App\Mail\InvoiceSubmittedNotification;
use App\Models\Company;
use App\Models\Invoice;
use App\Utils\EmailUtils;
use Illuminate\Support\Facades\Crypt;
use Tenancy\Facades\Tenancy;

class InvoiceSubmittedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  InvoiceSubmitted  $event
     * @return void
     */
    public function handle(InvoiceSubmitted $event)
    {
        /**
         * @var Invoice
         */
        $invoice = $event->getInvoice();
        /**
         * @var Company
         */
        $company = Company::find($event->getTenant());
        Tenancy::setTenant($company);

        $emailTemplate = $invoice->emailTemplate;

        if (empty($emailTemplate)) {
            throw new \Exception('Email not submitted: Email template not found.');
        }

        $cc = [];
        EmailUtils::pushEmail($cc, $emailTemplate->cc_addresses);
        $invoice->project->salesPersons->each(function ($salesPerson) use (&$cc) {
            if (!empty($salesPerson->email)) {
                EmailUtils::pushEmail($cc, $salesPerson->email);
            }
        });
        $invoice->project->leadGens->each(function ($leadGen) use (&$cc) {
            if (!empty($leadGen->email)) {
                EmailUtils::pushEmail($cc, $leadGen->email);
            }
        });
        $smtpSetting = $emailTemplate->smtpSetting->toArray();
        $mailer = app()->makeWith('company.mailer', $smtpSetting);
        $mailer->cc($cc)->send(new InvoiceSubmittedNotification($event->getTenant(), $invoice->id));
        $this->setInvoiceToSubmitted($invoice);
    }

    private function setInvoiceToSubmitted($invoice)
    {
        $invoice->submitted_date = now();
        $invoice->save();
    }
}
