<?php

namespace App\Mail;

use App\Notifications\Mailable\TenancyMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UnpaidCommissionPaymentLogNotification extends TenancyMailable
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
        return $this->subject('Commission marked as unpaid')
          ->markdown('emails.commission_unpaid_mail')
          ->with([
              'amount'   => $this->amount,
          ]);
    }
}
