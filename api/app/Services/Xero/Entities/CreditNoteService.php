<?php


namespace App\Services\Xero\Entities;


use \XeroAPI\XeroPHP\Models\Accounting\CreditNotes;
use \XeroAPI\XeroPHP\Api\AccountingApi;

class CreditNoteService extends BaseEntity
{
    private CreditNotes $accounting;
    private string $tenant_id;
    private AccountingApi $apiInstance;

    public function __construct(AccountingApi $apiInstance, string $tenant_id)
    {
        $this->accounting     = new CreditNotes;
        $this->tenant_id      = $tenant_id;
        $this->apiInstance    = $apiInstance;
    }

    public function getCreditNotes()
    {
        return $this->apiInstance->getCreditNotes($this->tenant_id);
    }
}
