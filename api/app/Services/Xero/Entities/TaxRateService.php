<?php


namespace App\Services\Xero\Entities;


use Illuminate\Support\Facades\Log;
use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\ApiException;

class TaxRateService extends BaseEntity
{
    private string $tenant_id;
    private AccountingApi $apiInstance;

    public function __construct(AccountingApi $apiInstance, string $tenant_id)
    {
        $this->tenant_id      = $tenant_id;
        $this->apiInstance    = $apiInstance;
    }

    public function getTaxRatesFromXero(): array
    {
        $rates = [];
        try {
            $taxRates = $this->apiInstance->getTaxRates($this->tenant_id);

            foreach ($taxRates as $taxRate) {
                $m['name'] = $taxRate->getName();
                $m['value'] = $taxRate->getTaxType();
                array_push($rates, $m);
            }

            return $rates;
        } catch (ApiException $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }
}
