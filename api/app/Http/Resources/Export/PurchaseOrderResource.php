<?php

namespace App\Http\Resources\Export;

use App\Enums\Country;
use App\Enums\CurrencyCode;
use App\Enums\EntityModifierDescription;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Services\EmployeeService;
use App\Services\ResourceService;
use Illuminate\Support\Facades\App;

class PurchaseOrderResource extends ExtendedJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $companyCurrency = Company::find(getTenantWithConnection())->currency_code;
        $resourceService = App::make(ResourceService::class);
        $symbol = CurrencyCode::make($this->currency_code)->getValue();
        $resource = $resourceService->findBorrowedResource($this->resource_id);
        if (!$resource) {
            $employeeService = App::make(EmployeeService::class);
            $resource = $employeeService->findBorrowedEmployee($this->resource_id);
        }

        if (!$this->manual_input && $this->currency_code != $companyCurrency) {
            $totalNoMods = ceiling(entityPrice(PurchaseOrder::class, $this->id, true, $this->currency_rate_customer, false), 2);
            $totalWithMods = ceiling(entityPrice(PurchaseOrder::class, $this->id, false, $this->currency_rate_customer, true), 2);
            $totalAffectedByPriceModifiers = ceiling(entity_price_affected_by_modifiers(PurchaseOrder::class, $this->id, $this->currency_rate_customer), 2);
            $totalAmountWithoutVat = ceiling(entityPrice(PurchaseOrder::class, $this->id, false, $this->currency_rate_customer, false), 2);
        } else {
            $totalNoMods = entityPrice(PurchaseOrder::class, $this->id, true, 1, false);
            $totalWithMods = entityPrice(PurchaseOrder::class, $this->id, false, 1, true);
            $totalAffectedByPriceModifiers = ceiling(entity_price_affected_by_modifiers(PurchaseOrder::class, $this->id, 1), 2);
            $totalAmountWithoutVat = ceiling(entityPrice(PurchaseOrder::class, $this->id, false, 1, false), 2);
        }

        if ($this->total_vat == 0) {
            $vat = 0;
            $taxRate = 0;
        } else {
            $taxRate = empty($this->vat_percentage) ? getTaxRate($this->date, $this->legal_entity_id) : $this->vat_percentage;
            $vat = round($totalWithMods * ($taxRate / 100), 2);
        }

        $isCurrencyUSD = $resource->default_currency == CurrencyCode::USD()->getIndex();
        $isResourceFromUsa = $resource->country == Country::UnitedStatesOfAmerica()->getIndex();

        $transactionFee = $this->priceModifiers->first(function ($modifier) {
            return $modifier->description === EntityModifierDescription::transaction_fee()->__toString();
        });

        $transactionFeeAmount = 0;
        if (!empty($transactionFee) && $this->project->price_modifiers_calculation_logic) {
            $transactionFeeAmount = $transactionFee->quantity * $totalAmountWithoutVat / 100;
        }

        return [
        'VAR_DATE'               => $this->date ? $this->date->format('d/m/Y') : null,
        'VAR_DELIVERY_DATE'      => $this->delivery_date ? $this->delivery_date->format('d/m/Y') : null,
        'VAR_NUMBER'             => $this->number ?? null,
        'VAR_REFERENCE'          => $this->reference ?? null,
        'VAR_CURRENCY'           => !($resource->default_currency === null) ?
            CurrencyCode::make($resource->default_currency)->getValue()  :
            null,
        'VAR_TOTAL_PRICE'        => $this->currencyFormatter->formatCurrency($totalAmountWithoutVat + $vat + $transactionFeeAmount, $symbol),
        'VAR_TOTAL_VAT'          => $this->currencyFormatter->formatCurrency($vat, $symbol),
        'VAR_WITHOUT_VAT'        => $this->currencyFormatter->formatCurrency($totalAmountWithoutVat, $symbol),
        'VAR_TOTAL_WITHOUT_MODS' => $this->currencyFormatter->formatCurrency($totalNoMods, $symbol),
        'VAR_TOTAL_AFFECTED_BY_MODS' => $this->currencyFormatter->formatCurrency($totalAffectedByPriceModifiers, $symbol),
        'VAR_PM_NAME'            => $this->project->projectManager->name ?? null,
        'VAR_PM_FIRST_NAME'      => $this->project->projectManager->first_name ?? null,
        'VAR_PM_LAST_NAME'       => $this->project->projectManager->last_name ?? null,
        'VAR_PM_EMAIL'           => $this->project->projectManager->email ?? null,
        'VAR_PM_PHONE_NUMBER'    => $this->project->projectManager->phone_number ?? null,
        'VAR_R_NAME'             => $resource->name ?? null,
        'VAR_R_FIRST_NAME'       => $resource->first_name ?? null,
        'VAR_R_LAST_NAME'        => $resource->last_name ?? null,
        'VAR_R_EMAIL'            => $resource->email ?? null,
        'VAR_R_TAX_NUMBER'       => $resource->tax_number ?? null,
        'VAR_R_PHONE_NUMBER'     => $resource->phone_number ?? null,
        'VAR_R_ADDRESSLINE_1'    => $resource->addressline_1 ?? null,
        'VAR_R_ADDRESSLINE_2'    => $resource->addressline_2 ?? null,
        'VAR_R_CITY'             => $resource->city ?? null,
        'VAR_R_REGION'           => $resource->region ?? null,
        'VAR_R_POSTAL_CODE'      => $resource->postal_code ?? null,
        'VAR_R_COUNTRY'          => $resource->country ?
            Country::make($resource->country)->getValue() :
            null,
        'VAR_LEGAL_NAME'          => $this->legalEntity->name ?? null,
        'VAR_LEGAL_VAT'           => $this->legalEntity->vat_number ?? null,
        'VAR_LEGAL_ADDRESSLINE_1' => $this->legalEntity->address->addressline_1 ?? null,
        'VAR_LEGAL_ADDRESSLINE_2' => $this->legalEntity->address->addressline_2 ?? null,
        'VAR_LEGAL_CITY'          => $this->legalEntity->address->city ?? null,
        'VAR_LEGAL_REGION'        => $this->legalEntity->address->region ?? null,
        'VAR_LEGAL_POSTAL_CODE'   => $this->legalEntity->address->postal_code ?? null,
        'VAR_LEGAL_COUNTRY'       => $this->legalEntity->address->country ?
            Country::make($this->legalEntity->address->country)->getValue() :
            null,
        'VAR_BANK_ADDRESSLINE_1'  => $isCurrencyUSD ? $this->legalEntity->americanBank->address->addressline_1 :
            $this->legalEntity->europeanBank->address->addressline_1,
        'VAR_BANK_ADDRESSLINE_2'  => $isCurrencyUSD ? $this->legalEntity->americanBank->address->addressline_2 :
            $this->legalEntity->europeanBank->address->addressline_2,
        'VAR_BANK_CITY'           => $isCurrencyUSD ? $this->legalEntity->americanBank->address->city :
            $this->legalEntity->europeanBank->address->city,
        'VAR_BANK_REGION'         => $isCurrencyUSD ? $this->legalEntity->americanBank->address->region :
            $this->legalEntity->europeanBank->address->region,
        'VAR_BANK_POSTAL_CODE'    => $isCurrencyUSD ? $this->legalEntity->americanBank->address->postal_code :
            $this->legalEntity->europeanBank->address->postal_code,
        'VAR_BANK_COUNTRY'        => $isCurrencyUSD ? ($this->legalEntity->americanBank->address->country ?
            Country::make($this->legalEntity->americanBank->address->country)->getValue() : null)
            : ($this->legalEntity->europeanBank->address->country ?
                Country::make($this->legalEntity->europeanBank->address->country)->getValue() : null),
        $this->legalEntity->europeanBank->address->country,
        'VAR_BANK_NAME'           => $isCurrencyUSD ? $this->legalEntity->americanBank->name :
            $this->legalEntity->europeanBank->name,
        'VAR_BANK_IBAN'           => $this->legalEntity->europeanBank->iban ?? null,
        'VAR_BANK_BIC'            => $this->legalEntity->europeanBank->bic ?? null,
        'VAR_BANK_ACCOUNT_NR'     => $isResourceFromUsa && $this->legalEntity->americanBank->usa_account_number ?
            $this->legalEntity->americanBank->usa_account_number : $this->legalEntity->americanBank->account_number,
        'VAR_BANK_ROUTING_NR'     => $isResourceFromUsa && $this->legalEntity->americanBank->usa_routing_number ?
            $this->legalEntity->americanBank->usa_routing_number : $this->legalEntity->americanBank->routing_number,
        'VAR_U_NAME'              => auth()->user()->name ?? null,
        'VAR_U_FIRST_NAME'        => auth()->user()->first_name ?? null,
        'VAR_U_LAST_NAME'         => auth()->user()->last_name ?? null,
        'VAR_U_EMAIL'             => auth()->user()->email ?? null,
        'VAR_TAX_RATE'            => $taxRate,
        'VAR_TRANS_FEE'          => $this->currencyFormatter->formatCurrency($transactionFeeAmount, $symbol),
        'VAR_TRANS_FEE_LABEL'    => 'TRANSACTION FEE',
        'VAR_USDC_WALLET_ADDRESS' => 'USDC wallet address: ' . $this->legalEntity->usdc_wallet_address
        ];
    }
}
