<?php

namespace App\Listeners;

use App\Events\InvoiceSubmittedSuccessfully;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Tenancy\Facades\Tenancy;

class InvoiceSubmittedSuccessfullyListener
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
     * @param  InvoiceSubmittedSuccessfully  $event
     * @return void
     */
    public function handle(InvoiceSubmittedSuccessfully $event)
    {
        Tenancy::setTenant(Company::find($event->getTenant()));
        $invoice = Invoice::find($event->getInvoiceId());
        if (!empty($invoice) && $invoice->customer_notified_at == null) {
            $invoice->update(['customer_notified_at' => now()]);
        }
    }
}
