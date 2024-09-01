<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Notifications\Mailable\TenancyMailable;
use App\Services\ResourceService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Tenancy\Facades\Tenancy;

class PurchaseOrderAuthorised extends TenancyMailable
{
    use Queueable, SerializesModels;

    public $tenant_key;
    protected $po_id;
    protected $token;
    protected $resourceId;
    protected ResourceService $resourceService;

    /**
     * Create a new message instance.
     *
     * @param $tenant_key
     * @param $po_id
     * @param $token
     */
    public function __construct($tenant_key, $po_id, $token, $resourceId)
    {
        $this->tenant_key = $tenant_key;
        $this->po_id = $po_id;
        $this->token = $token;
        $this->resourceId = $resourceId;
        $this->resourceService = App::make(ResourceService::class);
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
        $po = PurchaseOrder::find($this->po_id);

        if ($po) {
            $resource = $this->resourceService->findBorrowedResource($this->resourceId);
            return $this->subject('New Purchase Order: '.$po->number)
            ->markdown('emails.po_authorised_mail')
            ->with([
                'poNumber' => $po->number,
                'url' => config('app.front_url').'/freelancers/' . $resource->company_id .'/' . $resource->id . '/' . $this->token
            ]);
        } else {
            throw new InvalidArgumentException('Purchase Order not found');
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
