<?php

namespace App\Services;

use App\Contracts\Repositories\GlobalTaxRateRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Contracts\Repositories\TaxRateRepositoryInterface;
use App\DTO\GlobalTaxRates\GlobalTaxRateDTO;
use App\Models\Company;
use App\Models\GlobalTaxRate;
use App\Models\TaxRate;
use App\Services\Xero\Auth\AuthService;
use App\Services\Xero\XeroService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tenancy\Facades\Tenancy;

/**
 * Class TaxRateService
 * Set and manage tax rates for legal entities
 */
class TaxRateService
{
    protected TaxRateRepositoryInterface $taxRateRepository;

    protected GlobalTaxRateRepositoryInterface $globalTaxRateRepository;

    protected CompanySettingsService $companySettingsService;

    public function __construct(
        TaxRateRepositoryInterface $taxRateRepository,
        GlobalTaxRateRepositoryInterface $globalTaxRateRepository,
        CompanySettingsService $companySettingsService
    ) {
        $this->taxRateRepository = $taxRateRepository;
        $this->globalTaxRateRepository = $globalTaxRateRepository;
        $this->companySettingsService = $companySettingsService;
    }

    public function createRate(array $payload): void
    {
        $this->taxRateRepository->create($payload);
    }

    public function index(string $legalEntityId): Collection
    {
        $taxRates = $this->globalTaxRateRepository->getAllBy('legal_entity_id', $legalEntityId);

        return $this->mapToXeroTaxRatesName($legalEntityId, $taxRates);
    }

    public function view(string $legalEntityId, string $taxRateId): Collection
    {
        $taxRate = $this->globalTaxRateRepository->getAllBy('id', $taxRateId);
        if ($taxRate->first()->legal_entity_id != $legalEntityId) {
            throw new ModelNotFoundException();
        }

        return $this->mapToXeroTaxRatesName($legalEntityId, $taxRate);
    }

    public function create(GlobalTaxRateDTO $taxRateDTO): GlobalTaxRate
    {
        return $this->globalTaxRateRepository->create($taxRateDTO->toArray());
    }

    public function update(string $taxRateId, GlobalTaxRateDTO $taxRateDTO): GlobalTaxRate
    {
        $this->globalTaxRateRepository->update($taxRateId, $taxRateDTO->toArray());

        return $this->globalTaxRateRepository->firstById($taxRateId);
    }

    public function delete(string $legalEntityId, string $taxRateId): void
    {
        $taxRate = $this->globalTaxRateRepository->firstById($taxRateId);
        if ($taxRate->legal_entity_id != $legalEntityId) {
            throw new ModelNotFoundException();
        }

        $taxRate->delete();
    }

    private function mapToXeroTaxRatesName(string $legalEntityId, Collection $collection)
    {
        $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
        $legalEntity = $legalEntityRepository->firstById($legalEntityId);

        if ((App::makeWith(AuthService::class, ['legalEntity' => $legalEntity]))->exists()) {
            $xeroRates = (App::makeWith(XeroService::class, ['legalEntity' => $legalEntity]))->getTaxRates();

            if ($collection) {
                $collection = $collection->map(function ($rate) use ($xeroRates) {
                    foreach ($xeroRates as $xeroRate) {
                        if ($rate->xero_sales_tax_type == $xeroRate['value']) {
                              $rate->xero_sales_tax_type = $xeroRate['name'];
                        }
                        if ($rate->xero_purchase_tax_type == $xeroRate['value']) {
                              $rate->xero_purchase_tax_type = $xeroRate['name'];
                        }
                    }

                    return $rate;
                });
            }
        }

        return $collection;
    }

    public function migrateTaxRateDataTenants(): void
    {
        DB::beginTransaction();

        try {
            $taxRates = $this->taxRateRepository->getAll();

            if ($taxRates->isNotEmpty()) {
                $companyId = getTenantWithConnection();
                foreach ($taxRates as $rate) {
                    $payload = [
                    'tax_rate'               => $rate->tax_rate,
                    'start_date'             => $rate->start_date,
                    'end_date'               => $rate->end_date,
                    'xero_sales_tax_type'    => $rate->xero_sales_tax_type,
                    'xero_purchase_tax_type' => $rate->xero_purchase_tax_type,
                    'belonged_to_company_id' => $companyId,
                    ];

                    $this->globalTaxRateRepository->create($payload);
                }
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    public function rollbackTaxRateDataTenants(): void
    {
        DB::beginTransaction();

        try {
            $companyId = getTenantWithConnection();
            $taxRates = $this->globalTaxRateRepository->getAllBy('belonged_to_company_id', $companyId);

            if ($taxRates->isNotEmpty()) {
                foreach ($taxRates as $rate) {
                    $payload = [
                    'tax_rate'               => $rate->tax_rate,
                    'start_date'             => $rate->start_date,
                    'end_date'               => $rate->end_date,
                    'xero_sales_tax_type'    => $rate->xero_sales_tax_type,
                    'xero_purchase_tax_type' => $rate->xero_purchase_tax_type,
                    ];

                    $this->taxRateRepository->create($payload);
                }
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    public function getCurrentTaxRate(string $legalEntityId): float
    {

        $taxRate = $this->globalTaxRateRepository->getTaxRateForPeriod($legalEntityId, now());
        $request = app('request');
        $companyId = $request->input('company');
        $companySetting = $this->companySettingsService->view($companyId);

        if ($taxRate) {
            return min($taxRate->tax_rate, $companySetting->vat_max_value);
        }

        return $companySetting->vat_default_value;
    }
}
