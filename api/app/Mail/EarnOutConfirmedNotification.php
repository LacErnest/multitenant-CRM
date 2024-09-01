<?php

namespace App\Mail;

use App\Notifications\Mailable\TenancyMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EarnOutConfirmedNotification extends TenancyMailable
{
    use Queueable, SerializesModels;

    protected string $user;

    protected string $quarter;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $user, string $quarter)
    {
        $this->user = $user;
        $this->quarter = $quarter;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Earn out confirmed')
          ->markdown('emails.earnout_confirmed_mail')
          ->with([
              'user'     => $this->user,
              'quarter'   => $this->quarter,
          ]);
    }
}
