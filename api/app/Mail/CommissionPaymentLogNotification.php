<?php

namespace App\Mail;

use App\Notifications\Mailable\TenancyMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommissionPaymentLogNotification extends TenancyMailable
{
    use Queueable, SerializesModels;

    protected string $user;

    protected string $amount;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $user, string $amount)
    {
        $this->user = $user;
        $this->amount = $amount;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Commission payment')
          ->markdown('emails.commission_paid_mail')
          ->with([
              'amount'   => $this->amount,
          ]);
    }
}
