<?php

namespace App\Listeners\EmailSentFactory;

use App\Events\InvoiceSubmittedSuccessfully;
use Tenancy\Hooks\Database\Events\Drivers\Creating;

class InvoiceSubmittedFactory implements EmailSentBuilder
{
    public function event($tenant, $event)
    {
        $invoiceId = $event->message->getHeaders()->get('X-Invoice-Id')->getFieldBody();
        return new InvoiceSubmittedSuccessfully($tenant, $invoiceId);
    }
}
