<?php

namespace App\Mail\Traits;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\SmtpSetting;
use Tenancy\Facades\Tenancy;

trait InvoiceMailableTrait
{
    /**
     * Get smtp settings from witch email must be sent
     * @return SmtpSetting | null
     */
    protected function getSmtpSettings()
    {
        $company = Company::find($this->tenant_key);
        if ($company) {
            Tenancy::setTenant($company);
            $invoice = Invoice::find($this->invoice_id);
            return $invoice->emailTemplate->smtpSetting ?? null;
        }
        return null;
    }

    /**
     * Get entity id for cookies setting
     * @return string
     */
    protected function getEntityKey(): string
    {
        return 'Invoice-Id';
    }

    /**
     * Get entity id for cookies setting
     * @return string
     */
    protected function getEntityId(): string
    {
        return $this->invoice_id;
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
