<?php

namespace App\Mail;

use App\Notifications\Mailable\TenancyMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EarnOutApprovalNotification extends TenancyMailable
{
    use Queueable, SerializesModels;

    protected string $owner;

    protected string $quarter;

    protected string $companyName;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $owner, string $quarter, string $companyName)
    {
        $this->owner = $owner;
        $this->quarter = $quarter;
        $this->companyName = $companyName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Earn out approved')
          ->markdown('emails.earnout_approved_mail')
          ->with([
              'owner'     => $this->owner,
              'company'   => $this->companyName,
              'quarter'   => $this->quarter,
          ]);
    }
}
