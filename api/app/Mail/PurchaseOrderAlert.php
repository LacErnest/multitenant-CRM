<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Notifications\Mailable\TenancyMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use Tenancy\Facades\Tenancy;

class PurchaseOrderAlert extends TenancyMailable
{
    use Queueable, SerializesModels;

    public $tenant_key;
    protected $poId;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($tenant_key, $poId)
    {
        $this->tenant_key = $tenant_key;
        $this->poId = $poId;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $company = Company::find($this->tenant_key);
        Tenancy::setTenant($company);
        $purchaseOrder = PurchaseOrder::find($this->poId);

        if ($purchaseOrder) {
            return $this->subject('Purchase order '.$purchaseOrder->number.' needs approval.')
            ->markdown('emails.purchase_order_alert_mail')
            ->with([
                'purchaseNumber' => $purchaseOrder->number,
                'paymentTerms' => $purchaseOrder->payment_terms,
                'url' => config('app.front_url').'/'.$company->id.'/projects/'.$purchaseOrder->project_id.'/purchase_orders/'.$purchaseOrder->id.'/edit',
            ]);
        } else {
            throw new InvalidArgumentException('Purchase order not found');
        }
    }

    /**
     * Get tenant id for cookies setting
     * @return string
     */
    protected function getTenantId(): string
    {
        return $this->tenant_key;
    }
}
