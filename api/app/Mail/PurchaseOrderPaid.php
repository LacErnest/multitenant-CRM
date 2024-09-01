<?php

namespace App\Mail;

use App\Notifications\Mailable\TenancyMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderPaid extends TenancyMailable
{
    use Queueable, SerializesModels;

    public string $tenantKey;
    protected string $resourceName;
    protected string $poNumber;
    protected string $amount;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $tenantKey, string $resourceName, string $poNumber, string $amount)
    {
        $this->tenantKey = $tenantKey;
        $this->resourceName = $resourceName;
        $this->poNumber = $poNumber;
        $this->amount = $amount;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Purchase order paid: '.$this->poNumber)
          ->markdown('emails.po_paid_mail')
          ->with([
              'poNumber' => $this->poNumber,
              'name' => $this->resourceName,
              'amount' => $this->amount,
          ]);
    }

    /**
     * Get tenant id for cookies setting
     * @return string
     */
    protected function getTenantId(): string
    {
        return $this->tenantKey;
    }
}
