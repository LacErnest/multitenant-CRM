<?php

namespace App\Http\Resources\Export;

use App\Enums\CurrencyCode;
use App\Enums\EntityModifierDescription;
use App\Enums\PurchaseOrderStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\PurchaseOrder;

class OrderReportResource extends ExtendedJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $company = Company::find(getTenantWithConnection());
        $companyCurrency = $company->currency_code;

        $symbol = CurrencyCode::make($companyCurrency)->getValue();

        if ($this->manual_input) {
            $symbol = CurrencyCode::make($this->currency_code)->getValue();
        }

        if (!$this->manual_input && $this->currency_code != $companyCurrency) {
            $totalNoMods = ceiling(entityPrice(Order::class, $this->id, true, $this->currency_rate_customer, false), 2);
            $totalWithMods = ceiling(entityPrice(Order::class, $this->id, false, $this->currency_rate_customer, true), 2);
            $totalAffectedByPriceModifiers = ceiling(entity_price_affected_by_modifiers(Order::class, $this->id, $this->currency_rate_customer), 2);
            $totalAmountWithoutVat = ceiling(entityPrice(Order::class, $this->id, false, $this->currency_rate_customer, false), 2);
        } else {
            $totalNoMods = entityPrice(Order::class, $this->id, true, 1, false);
            $totalWithMods = entityPrice(Order::class, $this->id, false, 1, true);
            $totalAffectedByPriceModifiers = ceiling(entity_price_affected_by_modifiers(Order::class, $this->id, 1), 2);
            $totalAmountWithoutVat = ceiling(entityPrice(Order::class, $this->id, false, 1, false), 2);
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

        $poStats = [
          'total_no_mods' => 0,
          'total_with_mods' => 0,
          'total_affected_by_price_modifiers' => 0,
          'total_without_vat' => 0,
          'total_vat' => 0,
          'tax_rate' => 0,
          'transaction_fee_amount' => 0,
          'total_price' => 0
        ];

        foreach ($this->project->purchaseOrders as $po) {
            $stats = getEntityStats(PurchaseOrder::class, $po->id, $this);
            foreach ($stats as $key => $value) {
                if (array_key_exists($key, $poStats)) {
                    $poStats[$key] += $value;
                }
            }
        }

        $employeesCosts = $this->project->employees->sum(function ($employee) {
            $rate = $employee->pivot->currency_rate_dollar ?? 1;
            return $employee->pivot->employee_cost * $rate;
        });

        $totalPrice = convertEntityAmountToCompanyCurrencyCode($totalAmountWithoutVat + $vat + $transactionFeeAmount, $this);

        $shadowsCosts = getShadowsCosts(Order::class, $this->id);
        $po = $this->project->purchaseOrders()->whereIn('status', [
          PurchaseOrderStatus::submitted()->getIndex(),
          PurchaseOrderStatus::completed()->getIndex(), PurchaseOrderStatus::authorised()->getIndex(),
          PurchaseOrderStatus::billed()->getIndex(), PurchaseOrderStatus::paid()->getIndex()
        ])->get();
        $poPotentials = $this->project->purchaseOrders()->whereNotIn('status', [PurchaseOrderStatus::rejected()->getIndex(), PurchaseOrderStatus::cancelled()->getIndex()])->get();
        $poPotentialCosts = $poPotentials->sum('total_price') - $poPotentials->sum('total_vat');
        $costs = $po->sum('total_price');
        $vat = $po->sum('total_vat');
        $budget = $this->total_price - $this->total_vat;
        $totalCosts = ($costs - $vat) + $this->project->employee_costs + $shadowsCosts['shadows_total_costs'];
        $totalPotentialCosts = $poPotentialCosts + $this->project->employee_costs + $shadowsCosts['shadows_total_potential_costs'];
        $markup = $budget > 0 ? flooring(safeDivide($budget - $totalCosts, $budget) * 100, 2) : null;

        return [
        'VAR_DATE'               => $this->date ? $this->date->format('d/m/Y') : null,
        'VAR_DEADLINE'           => $this->deadline ? $this->deadline->format('d/m/Y') : null,
        'VAR_NUMBER'             => $this->number ?? null,
        'VAR_REFERENCE'          => $this->reference ?? null,
        'VAR_CURRENCY'           => !($this->currency_code === null) ? CurrencyCode::make($this->currency_code)->getValue() : null,
        'VAR_TOTAL_PRICE'        => $this->manual_input ? $this->currencyFormatter->formatCurrency($this->manual_price, $symbol) :
            $this->currencyFormatter->formatCurrency($totalPrice, $symbol),
        'VAR_TOTAL_VAT'          => $this->manual_input ? $this->currencyFormatter->formatCurrency($this->manual_vat, $symbol) :
            $this->currencyFormatter->formatCurrency($vat, $symbol),
        'VAR_WITHOUT_VAT'        => $this->currencyFormatter->formatCurrency(convertEntityAmountToCompanyCurrencyCode($totalAmountWithoutVat, $this, $this->manual_input), $symbol),
        'VAR_TOTAL_WITHOUT_MODS' => $this->currencyFormatter->formatCurrency(convertEntityAmountToCompanyCurrencyCode($totalNoMods, $this), $symbol),
        'VAR_TOTAL_AFFECTED_BY_MODS' => $this->currencyFormatter->formatCurrency(convertEntityAmountToCompanyCurrencyCode($totalAffectedByPriceModifiers, $this), $symbol),
        'VAR_PM_NAME'            => $this->project->projectManager->name ?? null,
        'VAR_PM_FIRST_NAME'      => $this->project->projectManager->first_name ?? null,
        'VAR_PM_LAST_NAME'       => $this->project->projectManager->last_name ?? null,
        'VAR_PM_EMAIL'           => $this->project->projectManager->email ?? null,
        'VAR_PM_PHONE_NUMBER'    => $this->project->projectManager->phone_number ?? null,
        'VAR_C_NAME'             => $this->project->contact->customer->name ?? null,
        'VAR_C_EMAIL'            => $this->project->contact->customer->email ?? null,
        'VAR_C_TAX_NUMBER'       => $this->project->contact->customer->tax_number ?? null,
        'VAR_C_DEFAULT_CURRENCY' => !($this->project->contact->customer->default_currency === null) ?
            CurrencyCode::make($this->project->contact->customer->default_currency)->getValue() :
            null,

          'VAR_TRANS_FEE'          => $this->currencyFormatter->formatCurrency($transactionFeeAmount, $symbol),
          'VAR_TRANS_FEE_LABEL'    => 'TRANSACTION FEE',
          'VAR_USDC_WALLET_ADDRESS' => 'USDC wallet address: ' . $this->legalEntity->usdc_wallet_address,

          'VAR_PO_TOTAL_PRICE'        => $this->currencyFormatter->formatCurrency($poStats['total_price'], $symbol),
          'VAR_PO_TOTAL_VAT'          => $this->currencyFormatter->formatCurrency($poStats['total_vat'], $symbol),
          'VAR_PO_WITHOUT_VAT'        => $this->currencyFormatter->formatCurrency($poStats['total_without_vat'], $symbol),
          'VAR_PO_TOTAL_WITHOUT_MODS' => $this->currencyFormatter->formatCurrency($poStats['total_no_mods'], $symbol),
          'VAR_PO_TOTAL_AFFECTED_BY_MODS' => $this->currencyFormatter->formatCurrency($poStats['total_affected_by_price_modifiers'], $symbol),
          'VAR_PO_TRANS_FEE'          => $this->currencyFormatter->formatCurrency($poStats['transaction_fee_amount'], $symbol),
          'VAR_PO_TRANS_FEE_LABEL'    => 'TRANSACTION FEE',
          'VAR_MARKUP'                => $markup.'%',
          'VAR_POTENTIAL_MARKUP'      => ($budget > 0 ? flooring(safeDivide($budget - $totalPotentialCosts, $budget) * 100, 2) : 0).'%',
          'VAR_GROSS_MARGIN' =>       $this->currencyFormatter->formatCurrency(ceiling($budget - $totalCosts, 2), $symbol),
          'VAR_POTENTIAL_GM' =>       $this->currencyFormatter->formatCurrency(ceiling($budget - $totalPotentialCosts, 2), $symbol),
          'VAR_POTENTIAL_COSTS' =>       $this->currencyFormatter->formatCurrency(round($totalPotentialCosts, 2), $symbol),
          'VAR_COSTS' =>                  $this->currencyFormatter->formatCurrency(round($totalCosts), $symbol),
          'VAR_E_TOTAL_COSTS'          => $this->currencyFormatter->formatCurrency($employeesCosts, $symbol),
        ];
    }
}
