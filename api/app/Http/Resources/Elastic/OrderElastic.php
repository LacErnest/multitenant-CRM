<?php

namespace App\Http\Resources\Elastic;

use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Mail\MarkUpNotification;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Mail;

class OrderElastic extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->syncOriginal();

        $shadowPrice = 0;
        $shadowPriceUsd = 0;
        $shadowVat = 0;
        $shadowVatUsd = 0;
        $allInvoicesPaid = false;
        $markup = null;
        $customerTotalPrice = 0;
        $customerGm = 0;
        $customerCosts = 0;
        $customerPotentialGm = 0;
        $customerPotentialGmCosts = 0;
        $intraCompany = false;
        $intraCompanyId = null;

        $invoices = $this->invoices()->where([
          ['type', InvoiceType::accrec()->getIndex()],
          ['status', '!=', InvoiceStatus::unpaid()->getIndex()],
          ['status', '!=', InvoiceStatus::cancelled()->getIndex()]
        ])->orderByDesc('updated_at')->get();

        if ($invoices->count() != 0 && $invoices->count() == $invoices->whereNotNull('pay_date')->count()) {
            $allInvoicesPaid = true;
        }

        if ($this->project) {
            $shadowsCosts = getShadowsCosts(Order::class, $this->id);
            $po = $this->project->purchaseOrders()->whereIn('status', [
            PurchaseOrderStatus::submitted()->getIndex(),
            PurchaseOrderStatus::completed()->getIndex(), PurchaseOrderStatus::authorised()->getIndex(),
            PurchaseOrderStatus::billed()->getIndex(), PurchaseOrderStatus::paid()->getIndex()
            ])->get();
            $poPotentials = $this->project->purchaseOrders()->whereNotIn('status', [PurchaseOrderStatus::rejected()->getIndex(), PurchaseOrderStatus::cancelled()->getIndex()])->get();
            $poPotentialCosts = $poPotentials->sum('total_price') - $poPotentials->sum('total_vat');
            $poPotentialCostsUSD = $poPotentials->sum('total_price_usd') - $poPotentials->sum('total_vat_usd');
            $costs = $po->sum('total_price');
            $vat = $po->sum('total_vat');
            $costs_usd = $po->sum('total_price_usd');
            $vat_usd = $po->sum('total_vat_usd');
            $budget = $this->total_price - $this->total_vat;
            $budgetUsd = $this->total_price_usd - $this->total_vat_usd;
            $totalCosts = ($costs - $vat) + $this->project->employee_costs + $shadowsCosts['shadows_total_costs'];
            $totalCostsUSD = ($costs_usd - $vat_usd) + $this->project->employee_costs_usd + $shadowsCosts['shadows_total_costs_usd'];
            $totalPotentialCosts = $poPotentialCosts + $this->project->employee_costs + $shadowsCosts['shadows_total_potential_costs'];
            $totalPotentialCostsUSD = $poPotentialCostsUSD + $this->project->employee_costs_usd + $shadowsCosts['shadows_total_potential_costs_usd'];
            ;

            if ($this->master) {
                $company = Company::find(getTenantWithConnection());

                if ($this->manual_input) {
                    if ($company->currency_code == CurrencyCode::EUR()->getIndex()) {
                        $curentcyRateToEUR = safeDivide(1, $this->currency_rate_customer);
                        $curentcyRateToUSD = $this->currency_rate_company;
                    } else {
                        $curentcyRateToEUR = $this->currency_rate_company * safeDivide(1, $this->currency_rate_customer);
                        $curentcyRateToUSD = safeDivide(1, $this->currency_rate_company);
                    }
                    $shodowsTotalPrice = entityShadowPrices(Order::class, $this->id, $company->id);
                    $shadowPrice = $shodowsTotalPrice * $curentcyRateToEUR;
                    $shadowPriceUsd = $shodowsTotalPrice * $curentcyRateToUSD;
                } else {
                    if ($company->currency_code == CurrencyCode::EUR()->getIndex()) {
                        $shadowPrice = entityShadowPrices(Order::class, $this->id, $company->id);
                        $shadowPriceUsd = $shadowPrice * $this->currency_rate_company;
                    } else {
                        $shadowPriceUsd = entityShadowPrices(Order::class, $this->id, $company->id);
                        $shadowPrice = $shadowPriceUsd * $this->currency_rate_company;
                    }
                }

                if (VatStatus::isAlways($this->vat_status)) {
                    $taxIsApplicable = true;
                } elseif (VatStatus::isNever($this->vat_status)) {
                    $taxIsApplicable = false;
                } elseif ($this->project->contact->customer->non_vat_liable) {
                    $taxIsApplicable = true;
                } else {
                    $taxIsApplicable = $this->project->contact->customer->billing_address->country == $this->legalEntity->address->country;
                }

                if ($taxIsApplicable) {
                    $taxRate = empty($this->vat_percentage) ? getTaxRate($this->date, $this->legal_entity_id) : $this->vat_percentage;
                    $shadowVat = $shadowPrice * ($taxRate / 100);
                    $shadowPrice += $shadowVat;
                    $shadowVatUsd = $shadowPriceUsd * ($taxRate / 100);
                    $shadowPriceUsd += $shadowVatUsd;
                }
            }

            $markup = $budget > 0 ? flooring(safeDivide($budget - $totalCosts, $budget) * 100, 2) : null;
            $markupUsd = $budgetUsd > 0 ? flooring(safeDivide($budgetUsd - $totalCostsUSD, $budgetUsd) * 100, 2) : null;
            if (!empty($this->currency_rate_customer)) {
                $company = Company::find(getTenantWithConnection());

                if ($this->currency_code == CurrencyCode::USD()->getIndex()) {
                    $customerTotalPrice = ceiling($this->total_price_usd, 2);
                    $customerGm = ceiling($budgetUsd - $totalCostsUSD, 2);
                    $customerCosts = round($totalCostsUSD, 2);
                    $customerPotentialGm = ceiling($budgetUsd - $totalPotentialCostsUSD, 2);
                    $customerPotentialGmCosts = round($totalPotentialCostsUSD, 2);
                } elseif ($this->currency_code == CurrencyCode::EUR()->getIndex()) {
                    $customerTotalPrice = ceiling($this->total_price, 2);
                    $customerGm = ceiling($budget - $totalCosts, 2);
                    $customerCosts = round($totalCosts, 2);
                    $customerPotentialGm = ceiling($budget - $totalPotentialCosts, 2);
                    $customerPotentialGmCosts = round($totalPotentialCosts, 2);
                } else {
                    $customerTotalPrice = $this->total_price * $this->currency_rate_customer;
                    $customerGm = ceiling($budget - $totalCosts, 2) * $this->currency_rate_customer;
                    $customerCosts = $totalCosts * $this->currency_rate_customer;
                    $customerPotentialGm = ceiling($budget - $totalPotentialCosts, 2) * $this->currency_rate_customer;
                    $customerPotentialGmCosts = $totalPotentialCosts * $this->currency_rate_customer;
                }
            }

            $customerId = $this->project->contact ? $this->project->contact->customer->id : null;

            if ($customerId) {
                $customer = Customer::find($customerId);
                if ($customer->intra_company) {
                    $intraCompany = true;
                    $company = Company::where('name', $customer->name)->first();
                    if ($company) {
                        $intraCompanyId = $company->id;
                    }
                }
            }
        }

        return
          [
              'id'                    => $this->id,
              'project_info'          => [
                  'id'                            => $this->project ? $this->project->id : null,
                  'po_cost'                       => $this->project ? ceiling($costs, 2) : 0,
                  'po_vat'                        => $this->project ? round($vat, 2) : 0,
                  'employee_cost'                 => $this->project ? ceiling($this->project->employee_costs, 2) : 0,
                  'po_cost_usd'                   => $this->project ? ceiling($costs_usd, 2) : 0,
                  'po_vat_usd'                    => $this->project ? round($vat_usd, 2) : 0,
                  'employee_cost_usd'             => $this->project ? ceiling($this->project->employee_costs_usd, 2) : 0,
                  'external_employee_cost'        => $this->project ? ceiling($this->project->external_employee_costs, 2) : 0,
                  'external_employee_cost_usd'    => $this->project ? ceiling($this->project->external_employee_costs_usd, 2) : 0,
              ],
              'project'               => $this->project ? $this->project->name : null,
              'project_id'            => $this->project ? $this->project->id : null,
              'project_manager'       => $this->project && $this->project->projectManager ? $this->project->projectManager->name : null,
              'project_manager_id'    => $this->project && $this->project->project_manager_id ? $this->project->project_manager_id : null,
              'contact'               => $this->project && $this->project->contact ? $this->project->contact->name : null,
              'contact_id'            => $this->project && $this->project->contact ? $this->project->contact->id : null,
              'customer'              => $this->project && $this->project->contact ? $this->project->contact->customer->name : null,
              'customer_id'           => $this->project && $this->project->contact ? $this->project->contact->customer->id : null,
              'quote'                 => $this->quote ? $this->quote->number : null,
              'quote_id'              => $this->quote ? $this->quote->id : null,
              'date'                  => $this->date->timestamp ?? null,
              'deadline'              => $this->deadline->timestamp ?? null,
              'delivered_at'          => $this->delivered_at->timestamp ?? null,
              'status'                => $this->status ?? null,
              'number'                => $this->number ?? null,
              'reference'             => $this->reference ?? null,
              'currency_code'         => $this->currency_code ?? null,
              'customer_currency_code' => $this->project && $this->project->contact && $this->project->contact->customer ? $this->project->contact->customer->default_currency : null,
              'total_price'           => ceiling($this->total_price, 2),
              'total_vat'             => round($this->total_vat, 2),
              'total_price_usd'       => ceiling($this->total_price_usd, 2),
              'total_vat_usd'         => round($this->total_vat_usd, 2),
              'customer_total_price'  => ceiling(get_total_price(Order::class, $this->id), 2),
              'created_at'            => $this->created_at->timestamp,
              'updated_at'            => $this->updated_at->timestamp ?? null,
              'legal_entity_id'       => $this->legal_entity_id,
              'legal_entity'          => $this->legalEntity ? $this->legalEntity->name : null,
              'markup'                => $markup,
              'markup_usd'            => $markupUsd,
              'costs'                 => round($totalCosts, 2),
              'costs_usd'             => round($totalCostsUSD, 2),
              'customer_costs'        => ceiling($customerCosts, 2),
              'gross_margin'          => ceiling($budget - $totalCosts, 2),
              'gross_margin_usd'      => ceiling($budgetUsd - $totalCostsUSD, 2),
              'customer_gross_margin' => ceiling($customerGm, 2),
              'potential_markup'      => $budget > 0 ? flooring(safeDivide($budget - $totalPotentialCosts, $budget) * 100, 2) : null,
              'potential_markup_usd'  => $budgetUsd > 0 ? flooring(safeDivide($budgetUsd - $totalPotentialCostsUSD, $budgetUsd) * 100, 2) : null,
              'potential_gm_usd'      => ceiling($budgetUsd - $totalPotentialCostsUSD, 2),
              'potential_gm'          => ceiling($budget - $totalPotentialCosts, 2),
              'customer_potential_gm' => ceiling($customerPotentialGm, 2),
              'potential_costs'       => round($totalPotentialCosts, 2),
              'potential_costs_usd'   => round($totalPotentialCostsUSD, 2),
              'customer_potential_costs' => ceiling($customerPotentialGmCosts, 2),
              'master'                => (bool)$this->master,
              'shadow'                => (bool)$this->shadow,
              'shadow_price'          => ceiling($shadowPrice, 2),
              'shadow_price_usd'      => ceiling($shadowPriceUsd, 2),
              'shadow_vat'            => round($shadowVat, 2),
              'shadow_vat_usd'        => round($shadowVatUsd, 2),
              'all_invoices_paid'     => $allInvoicesPaid,
              'intra_company' => $intraCompany,
              'intra_company_id' => $intraCompanyId,
              'currency_rate_company'=>$this->currency_rate_company,
              'currency_rate_customer'=>$this->currency_rate_customer,
          ];
    }
}
