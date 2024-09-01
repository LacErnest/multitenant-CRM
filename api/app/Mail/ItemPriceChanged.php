<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Service;
use App\Models\User;
use App\Notifications\Mailable\TenancyMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Tenancy\Facades\Tenancy;

class ItemPriceChanged extends TenancyMailable
{
    use Queueable, SerializesModels;

    public $tenant_key;
    protected $user_id;
    protected $service_id;
    protected $price;

    /**
     * Create a new message instance.
     *
     * @param $tenant_key
     * @param $user_id
     * @param $service_id
     * @param $price
     */
    public function __construct($tenant_key, $user_id, $service_id, $price)
    {
        $this->tenant_key = $tenant_key;
        $this->user_id = $user_id;
        $this->service_id = $service_id;
        $this->price = $price;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {


        $company = Company::find($this->tenant_key);
        if ($company) {
            $user = User::find($this->user_id);
            Tenancy::setTenant($company);
            $service = Service::find($this->service_id);

            if ($user && $service) {
                return $this->subject('Price of service changed')
                ->markdown('emails.price_changed_mail')
                ->with([
                'serviceName' => $service->name,
                'servicePrice' => $service->price,
                'newPrice' => $this->price,
                'user' => $user->name,
                ]);
            }
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
