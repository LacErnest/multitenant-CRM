<?php

namespace App\Http\Resources\Elastic;

use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\VatStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\Project;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Tenancy\Facades\Tenancy;

class QuoteElastic extends JsonResource
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

        $payDate = null;
        $quoteNumber = 1;
        $shadowPrice = 0;
        $shadowPriceUsd = 0;
        $shadowVat = 0;
        $shadowVatUsd = 0;
        $customerTotalPrice = 0;
        $customProject = null;
        $intraCompany = false;
        $intraCompanyId = null;

        if ($this->project) {
            $po = $this->project->purchaseOrders()->whereIn('status', [PurchaseOrderStatus::submitted()->getIndex(), PurchaseOrderStatus::authorised()->getIndex(), PurchaseOrderStatus::billed()->getIndex(), PurchaseOrderStatus::paid()->getIndex()])->get();
            $costs = $po->sum('total_price');
            $vat = $po->sum('total_vat');
            $costs_usd = $po->sum('total_price_usd');
            $vat_usd = $po->sum('total_vat_usd');

            if ($this->project->order && (OrderStatus::isDelivered($this->project->order->status) || OrderStatus::isInvoiced($this->project->order->status))) {
                $invoices = $this->project->order->invoices()->where([
                ['type', InvoiceType::accrec()->getIndex()],
                ['status', '!=', InvoiceStatus::unpaid()->getIndex()],
                ['status', '!=', InvoiceStatus::cancelled()->getIndex()]
                ])->orderByDesc('updated_at')->get();
                if ($invoices->count() != 0 && $invoices->count() == $invoices->whereNotNull('pay_date')->count()) {
                      $payDate = $invoices->first()->pay_date->timestamp ?? null;
                }
            }

            if ($this->project->order) {
                $quotes = $this->project->quotes()
                ->whereIn('status', [QuoteStatus::ordered()->getIndex(), QuoteStatus::invoiced()->getIndex()])
                ->orderBy('updated_at')
                ->get();

                if ($quotes->count() > 1) {
                    $quoteIndex = $quotes->search(function ($quote) {
                        return $quote->id == $this->id;
                    });

                    $quoteNumber = $quoteIndex + 1;
                }
            }

            if ($this->master) {
                $company = Company::find(getTenantWithConnection());
                if ($this->manual_input) {
                    if ($company->currency_code == CurrencyCode::EUR()->getIndex()) {
                        $curentcyRateToEUR = safeDivide(1, $this->currency_rate_customer, 1);
                        $curentcyRateToUSD = $this->currency_rate_company;
                    } else {
                        $curentcyRateToEUR = $this->currency_rate_company * safeDivide(1, $this->currency_rate_customer);
                        $curentcyRateToUSD = safeDivide(1, $this->currency_rate_company, 1);
                    }
                    $shodowsTotalPrice = entityShadowPrices(Quote::class, $this->id, $company->id);
                    $shadowPrice = $shodowsTotalPrice * $curentcyRateToEUR;
                    $shadowPriceUsd = $shodowsTotalPrice * $curentcyRateToUSD;
                } else {
                    if ($company->currency_code == CurrencyCode::EUR()->getIndex()) {
                        $shadowPrice = entityShadowPrices(Quote::class, $this->id, $company->id);
                        $shadowPriceUsd = $shadowPrice * $this->currency_rate_company;
                    } else {
                        $shadowPriceUsd = entityShadowPrices(Quote::class, $this->id, $company->id);
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
            if (Company::find(getTenantWithConnection())->currency_code == CurrencyCode::USD()->getIndex()) {
                $customerTotalPrice = $this->total_price_usd * $this->currency_rate_customer;
            } else {
                $customerTotalPrice = $this->total_price_usd * $this->currency_rate_customer;
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

            $salesPersons = $this->project->salesPersons;
            $leadGens = $this->project->leadGens;
        }

        return
          [
              'id' => $this->id,
              'xero_id' => $this->xero_id ?? null,
              'project_info' => [
                  'id' => $this->project ? $this->project->id : null,
                  'po_cost' => $this->project ? ceiling($costs, 2) : 0,
                  'po_vat' => $this->project ? ceiling($vat, 2) : 0,
                  'employee_cost' => $this->project ? ceiling($this->project->employee_costs, 2) : 0,
                  'po_cost_usd' => $this->project ? ceiling($costs_usd, 2) : 0,
                  'po_vat_usd' => $this->project ? ceiling($vat_usd, 2) : 0,
                  'employee_cost_usd' => $this->project ? ceiling($this->project->employee_costs_usd, 2) : 0,
              ],
              'project' => $this->project ? $this->project->name : null,
              'project_id' => $this->project ? $this->project->id : null,
              'contact' => $this->project && $this->project->contact ? $this->project->contact->name : null,
              'contact_id' => $this->project && $this->project->contact ? $this->project->contact->id : null,
              'sales_person' => $this->project && !$salesPersons->count() == 0 ? getNames($salesPersons) : null,
              'sales_person_id' => $this->project && !$salesPersons->count() == 0 ? $salesPersons->pluck('id') : null,
              'customer' => $this->project && $this->project->contact ? $this->project->contact->customer->name : null,
              'customer_id' => $this->project && $this->project->contact ? $this->project->contact->customer->id : null,
              'date' => $this->date->timestamp ?? null,
              'expiry_date' => $this->expiry_date->timestamp ?? null,
              'status' => $this->status ?? null,
              'reason_of_refusal' => $this->reason_of_refusal ?? null,
              'number' => $this->number ?? null,
              'reference' => $this->reference ?? null,
              'currency_code' => $this->currency_code ?? null,
              'customer_currency_code' => $this->project && $this->project->contact && $this->project->contact->customer ? $this->project->contact->customer->default_currency : null,
              'total_price' => ceiling($this->total_price ?? 0, 2),
              'total_vat' => round($this->total_vat ?? 0, 2),
              'total_price_usd' => ceiling($this->total_price_usd ?? 0, 2),
              'total_vat_usd' => round($this->total_vat_usd ?? 0, 2),
              'currency_rate_customer' => $this->currency_rate_customer,
              'customer_total_price' => ceiling(get_total_price(Quote::class, $this->id), 2),
              'created_at' => $this->created_at->timestamp,
              'updated_at' => $this->updated_at->timestamp ?? null,
              'order_id' => $this->project->order ? $this->project->order->id : null,
              'order' => $this->project->order ? $this->project->order->number : null,
              'legal_entity_id' => $this->legal_entity_id,
              'legal_entity' => $this->legalEntity ? $this->legalEntity->name : null,
              'order_total_price' => $this->project->order->total_price ?? null,
              'order_total_vat' => $this->project->order->total_vat ?? null,
              'order_delivered_at' => $this->project->order->delivered_at->timestamp ?? null,
              'order_paid_at' => $payDate,
              'currency_rate_company' => $this->currency_rate_company,
              'this_is_quote' => $quoteNumber,
              'master' => (bool)$this->master,
              'shadow' => (bool)$this->shadow,
              'shadow_price' => ceiling($shadowPrice, 2),
              'shadow_price_usd' => ceiling($shadowPriceUsd, 2),
              'shadow_vat' => round($shadowVat, 2),
              'shadow_vat_usd' => round($shadowVatUsd, 2),
              'second_sales_person' => $this->project && !$leadGens->count() == 0 ? getNames($leadGens) : null,
              'second_sales_person_id' => $this->project && !$leadGens->count() == 0 ? $leadGens->pluck('id') : null,
              'intra_company' => $intraCompany,
              'intra_company_id' => $intraCompanyId,
              'down_payment' => $this->down_payment,
              'down_payment_type' => $this->down_payment_type,
          ];
    }
}
