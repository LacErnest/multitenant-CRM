<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\Mailable\TenancyMailable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use Tenancy\Facades\Tenancy;

class QuoteNotApproved extends TenancyMailable
{
    use Queueable, SerializesModels;

    public $tenant_key;
    protected $quote_id;

    /**
     * Create a new message instance.
     *
     * @param $tenant_key
     * @param $quote_id
     */
    public function __construct($tenant_key, $quote_id)
    {
        $this->tenant_key = $tenant_key;
        $this->quote_id = $quote_id;
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
        $quote = Quote::find($this->quote_id);

        if ($quote) {
            return $this->subject('Quote '.$quote->number.' not yet approved')
            ->markdown('emails.quote_not_approved_mail')
            ->with([
                'quoteNumber' => $quote->number,
                'url' => config('app.front_url').'/'.$company->id.'/projects/'.$quote->project_id.'/quotes/'.$quote->id.'/edit',
            ]);
        } else {
            throw new InvalidArgumentException('Quote not found');
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
