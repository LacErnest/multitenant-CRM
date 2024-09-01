<?php

namespace App\Http\Resources\Export;

use App\Enums\Country;
use App\Enums\CurrencyCode;
use App\Enums\EntityModifierDescription;
use App\Enums\PurchaseOrderStatus;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Services\EmployeeService;
use App\Services\ResourceService;
use Illuminate\Support\Facades\App;

class PurchaseOrderReportResource extends ExtendedJsonResource
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
        $symbol = CurrencyCode::make($companyCurrency)->getValue();
        $resource = $resourceService->findBorrowedResource($this->resource_id);
        if (!$resource) {
            $employeeService = App::make(EmployeeService::class);
            $resource = $employeeService->findBorrowedEmployee($this->resource_id);
        }

        if (!$this->manual_input && $this->currency_code != $companyCurrency) {
            $totalWithMods = ceiling(entityPrice(PurchaseOrder::class, $this->id, false, $this->currency_rate_customer, true), 2);
            $totalAmountWithoutVat = ceiling(entityPrice(PurchaseOrder::class, $this->id, false, $this->currency_rate_customer, false), 2);
        } else {
            $totalWithMods = entityPrice(PurchaseOrder::class, $this->id, false, 1, true);
            $totalAmountWithoutVat = ceiling(entityPrice(PurchaseOrder::class, $this->id, false, 1, false), 2);
        }

        if ($this->total_vat == 0) {
            $vat = 0;
            $taxRate = 0;
        } else {
            $taxRate = empty($this->vat_percentage) ? getTaxRate($this->date, $this->legal_entity_id) : $this->vat_percentage;
            $vat = round($totalWithMods * ($taxRate / 100), 2);
        }


        $transactionFee = $this->priceModifiers->first(function ($modifier) {
            return $modifier->description === EntityModifierDescription::transaction_fee()->__toString();
        });

        $transactionFeeAmount = 0;
        if (!empty($transactionFee) && $this->project->price_modifiers_calculation_logic) {
            $transactionFeeAmount = $transactionFee->quantity * $totalAmountWithoutVat / 100;
        }

        $resource = null;
        if ($this->resource_id) {
            $resourceService = App::make(ResourceService::class);
            $resource = $resourceService->findBorrowedResource($this->resource_id);
            if (!$resource) {
                $employeeService = App::make(EmployeeService::class);
                $resource = $employeeService->findBorrowedEmployee($this->resource_id);
            }
        }

        $totalPrice = $totalAmountWithoutVat + $vat + $transactionFeeAmount;
        $totalPrice = convertEntityAmountToCompanyCurrencyCode($totalPrice, $this);
        return [
        'VAR_PO_DATE'               => $this->date ? $this->date->format('d/m/Y') : null,
        'VAR_PO_DELIVERY_DATE'      => $this->delivery_date ? $this->delivery_date->format('d/m/Y') : null,
        'VAR_PO_NUMBER'             => $this->number ?? null,
        'VAR_PO_STATUS'             => PurchaseOrderStatus::make($this->status)->__toString(),
        'VAR_REFERENCE'          => $this->reference ?? null,
        'VAR_PO_RESOURCE'          => $resource ? $resource->name : null,
        'VAR_PO_CURRENCY'           => !($resource->default_currency === null) ?
            CurrencyCode::make($resource->default_currency)->getValue()  :
            null,
        'VAR_PO_PRICE'   => $this->currencyFormatter->formatCurrency($totalPrice, $symbol),
        ];
    }
}
