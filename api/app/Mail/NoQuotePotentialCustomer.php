<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Notifications\Mailable\TenancyMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Tenancy\Facades\Tenancy;

class NoQuotePotentialCustomer extends TenancyMailable
{
    use Queueable, SerializesModels;

    protected $customer_id;
    protected $contact_id;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($customer_id, $contact_id)
    {
        $this->customer_id = $customer_id;
        $this->contact_id = $contact_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $customer = Customer::find($this->customer_id);
        $contact = Contact::find($this->contact_id);
        if ($customer) {
            return $this->subject('No Quote For Potential Customer')
            ->markdown('emails.no_quote_mail')
            ->with([
                'customerName' => $customer->name,
                'customerDate' => $customer->created_at,
                'contactName' => $contact ? $contact->first_name . ' ' . $contact->last_name : 'no contact',
                'contactPhone' => $contact ? $contact->phone_number : 'no contact',
                'contactEmail' => $contact ? $contact->email : 'no contact'
            ]);
        }
    }
}
