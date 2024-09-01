<?php

namespace App\Mail;

use App\Notifications\Mailable\TenancyMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuarterlyApprovalNotification extends TenancyMailable
{
    use Queueable, SerializesModels;

    protected string $quarter;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $quarter)
    {
        $this->quarter = $quarter;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Earn out available')
          ->markdown('emails.quarterly_approval_mail')
          ->with([
              'quarter'   => $this->quarter,
          ]);
    }
}
