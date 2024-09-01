<?php

namespace App\Http\Resources\Elastic;

use App\Enums\CreditNoteStatus;
use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\VatStatus;
use App\Http\Resources\CreditNote\CreditNoteResource;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\EmployeeService;
use App\Services\ResourceService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class InvoiceElastic extends JsonResource
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

        $last_paid_invoice = false;
        $legacyCustomer = false;
        $allInvoicesPaid = false;
        $intraCompany = false;
        $customer = null;
        $shadowPrice = 0;
        $shadowPriceUsd = 0;
        $shadowVat = 0;
        $shadowVatUsd = 0;
        $intraCompanyId = null;
        $total_paid_amount = 0;
        $total_paid_amount_usd = 0;
        $total_paid_vat = 0;
        $total_paid_vat_usd = 0;
        $last_payment_date = null;
        $customerTotalPrice = 0;
        $payDate = null;
        $quoteNumber = 0;
        $shadowTotalCosts = 0;
        $shadowTotalCostsUsd = 0;
        $taxIsApplicable = false;
        $totalRevenue = 0;
        $budget = 0;
        $budgetUsd = 0;
        $markup = 0;
        $markupUsd = 0;
        $invoiceTotalCost = 0;
        $invoiceTotalCostUsd = 0;

        if ($this->project) {
            $po = $this->project->purchaseOrders()->whereIn('status', [PurchaseOrderStatus::submitted()->getIndex(),
            PurchaseOrderStatus::authorised()->getIndex(), PurchaseOrderStatus::billed()->getIndex(),
            PurchaseOrderStatus::paid()->getIndex(), PurchaseOrderStatus::completed()->getIndex()])->get();
            $costs = $po->sum('total_price');
            $vat = $po->sum('total_vat');
            $costs_usd = $po->sum('total_price_usd');
            $vat_usd = $po->sum('total_vat_usd');
            $budget = $this->total_price - $this->total_vat;
            $budgetUsd = $this->total_price_usd - $this->total_vat_usd;
            if ($this->master) {
                  $shadowsCosts = getShadowsCosts(Order::class, $this->order->id);
                  $shadowTotalCosts = $shadowsCosts['shadows_total_costs'];
                  $shadowTotalCostsUsd = $shadowsCosts['shadows_total_costs_usd'];
                  $company = Company::find(getTenantWithConnection());
                if ($this->manual_input) {
                    if ($company->currency_code == CurrencyCode::EUR()->getIndex()) {
                        $curentcyRateToEUR = safeDivide(1, $this->currency_rate_customer);
                        $curentcyRateToUSD = $this->currency_rate_company;
                    } else {
                        $curentcyRateToEUR = $this->currency_rate_company * safeDivide(1, $this->currency_rate_customer);
                        $curentcyRateToUSD = safeDivide(1, $this->currency_rate_company);
                    }
                    $shodowsTotalPrice = entityShadowPrices(Invoice::class, $this->id, $company->id);
                    $shadowPrice = $shodowsTotalPrice * $curentcyRateToEUR;
                    $shadowPriceUsd = $shodowsTotalPrice * $curentcyRateToUSD;
                } else {
                    if ($company->currency_code == CurrencyCode::EUR()->getIndex()) {
                        $shadowPrice = entityShadowPrices(Invoice::class, $this->id, $company->id);
                        $shadowPriceUsd = $shadowPrice * $this->currency_rate_company;
                    } else {
                        $shadowPriceUsd = entityShadowPrices(Invoice::class, $this->id, $company->id);
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
                    if (!($this->legal_entity_id === null)) {
                        $taxIsApplicable = $this->project->contact->customer->billing_address->country == $this->legalEntity->address->country;
                    } else {
                                  $taxIsApplicable = false;
                    }
                }

                if ($taxIsApplicable) {
                    $taxRate = empty($this->vat_percentage) ? getTaxRate($this->date, $this->legal_entity_id) : $this->vat_percentage;
                    $shadowVat = $shadowPrice * ($taxRate / 100);
                    $shadowPrice += $shadowVat;
                    $shadowVatUsd = $shadowPriceUsd * ($taxRate / 100);
                    $shadowPriceUsd += $shadowVatUsd;
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
        }

        if ($this->order && InvoiceType::isAccrec($this->type)) {
            $invoices = $this->order->invoices()->where([
            ['type', InvoiceType::accrec()->getIndex()],
            ['status', InvoiceStatus::paid()->getIndex()],
            ])->orderByDesc('updated_at')->get();
            if ($invoices->count() != 0 && $invoices->count() == $invoices->whereNotNull('pay_date')->count()) {
                $allInvoicesPaid = true;
                $last_paid_invoice = true;
            }

            $totalRevenue = $invoices->sum('total_price') - $invoices->sum('total_vat');
            $totalRevenueUds = $invoices->sum('total_price_usd') - $invoices->sum('total_vat_usd');
            $invoiceRevenuePercentage = safeDivide($budget, $totalRevenue);
            $invoiceRevenuePercentageUsd = safeDivide($budgetUsd, $totalRevenueUds);
            $totalCosts = ($costs - $vat) + $this->project->employee_costs + $shadowTotalCosts;
            $totalCostsUSD = ($costs_usd - $vat_usd) + $this->project->employee_costs_usd + $shadowTotalCostsUsd;
            $invoiceTotalCost = $totalCosts * $invoiceRevenuePercentage;
            $invoiceTotalCostUsd = $totalCostsUSD * $invoiceRevenuePercentageUsd;
            $markup = $budget > 0 ? flooring(safeDivide($budget - $invoiceTotalCost, $budget) * 100, 2) : null;
            $markupUsd = $budgetUsd > 0 ? flooring(safeDivide($budgetUsd - $invoiceTotalCostUsd, $budgetUsd) * 100, 2) : null;
        }

        if ($this->payments) {
            $company = Company::find(getTenantWithConnection());

            if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
                $currencyRateEurToUSD = safeDivide(1, $this->currency_rate_company);
            } else {
                $currencyRateEurToUSD = $this->currency_rate_company;
            }

            if ($company->currency_code == CurrencyCode::EUR()->getIndex()) {
                $currencyRateToEUR = safeDivide(1, $this->currency_rate_customer);
            } else {
                $currencyRateToEUR = $this->currency_rate_company * safeDivide(1, $this->currency_rate_customer);
            }

            $total_paid_amount = $this->payments->sum('pay_amount') * $currencyRateToEUR;
            $total_paid_amount_usd = $total_paid_amount * $currencyRateEurToUSD;
            if ($taxIsApplicable) {
                $taxRate = empty($this->vat_percentage) ? getTaxRate($this->date, $this->legal_entity_id) : $this->vat_percentage;
                $total_paid_vat = $total_paid_amount * ($taxRate / 100);
                $total_paid_vat_usd = $total_paid_vat * $currencyRateEurToUSD;
            }

            if (!empty($this->project->order) && (OrderStatus::isDelivered($this->project->order->status) || OrderStatus::isInvoiced($this->project->order->status))) {
                $invoices = $this->project->order->invoices()
                          ->where([
                              ['type', InvoiceType::accrec()->getIndex()],
                              ['status', '!=', InvoiceStatus::unpaid()->getIndex()],
                              ['status', '!=', InvoiceStatus::cancelled()->getIndex()]
                          ])
                          ->orderByDesc('updated_at')
                          ->get();

                if (($invoices->count() != 0) && ($invoices->count() == $invoices->whereNotNull('pay_date')->count())) {
                      $payDate = $invoices->first()->pay_date->timestamp ?? null;
                }
            }
        }
        $resource = null;

        if ($this->purchaseOrder && $this->purchaseOrder->resource_id) {
            $resourceService = App::make(ResourceService::class);
            $resource = $resourceService->findBorrowedResource($this->purchaseOrder->resource_id);

            if (!$resource) {
                $employeeService = App::make(EmployeeService::class);
                $resource = $employeeService->findBorrowedEmployee($this->purchaseOrder->resource_id);
            }
        }

        $customerId = $this->project && $this->project->contact ? $this->project->contact->customer->id : null;

        if ($customerId) {
            $customer = Customer::find($customerId);

            if ($customer && $customer->legacyCompanies()->where('company_id', getTenantWithConnection())->exists()) {
                $legacyCustomer = true;
            }

            if ($customer->intra_company) {
                $intraCompany = true;
                $company = Company::where('name', $customer->name)->first();

                if ($company) {
                    $intraCompanyId = $company->id;
                }
            }
        }

        if ($this->currency_rate_customer) {
            $customerTotalPrice = get_total_price(Invoice::class, $this->id);
        }

        $salesPersons = $this->project ? $this->project->salesPersons : [];
        $leadGens = $this->project ? $this->project->leadGens : [];

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
                  'shadow_costs' => $shadowTotalCosts,
                  'shadow_costs_usd' => $shadowTotalCostsUsd,
                  'employee_cost_usd' => $this->project ? ceiling($this->project->employee_costs_usd, 2) : 0,
                  'external_employee_cost' => $this->project ? ceiling($this->project->external_employee_costs, 2) : 0,
                  'external_employee_cost_usd' => $this->project ? ceiling($this->project->external_employee_costs_usd, 2) : 0,
              ],
              'project' => $this->project->name ?? null,
              'project_id' => $this->project->id ?? null,
              'contact' => $this->project->contact->name ?? null,
              'contact_id' => $this->project->contact->id ?? null,
              'customer' => $customer->name ?? null,
              'customer_id' => $customerId,
              'legacy_customer' => $legacyCustomer,
              'quote_id' => $this->order->quote->id ?? null,
              'quote_status' => $this->order->quote->status ?? null,
              'quote_date' => $this->order->quote->date->timestamp ?? null,
              'quote' => $this->order->quote->number ?? null,
              'this_is_quote' => $quoteNumber,
              'order' => $this->order->number ?? null,
              'order_id' => $this->order->id ?? null,
              'order_status' => $this->order->status ?? null,
              'order_paid_at' => $payDate,
              'is_last_paid_invoice' => $this->order ? $last_paid_invoice : false,
              'currency_rate_company' => $this->currency_rate_company,
              'currency_rate_customer'=>$this->currency_rate_customer,
              'purchase_order' => $this->purchaseOrder->number ?? null,
              'purchase_order_id' => $this->purchase_order_id ?? null,
              'resource' => $resource->name ?? null,
              'resource_id' => $resource->id ?? null,
              'type' => $this->type ?? null,
              'date' => $this->date->timestamp ?? null,
              'due_date' => $this->due_date->timestamp ?? null,
              'close_date' => $this->close_date->timestamp ?? null,
              'customer_notified_at' => $this->customer_notified_at->timestamp ?? null,
              'status' => $this->status ?? null,
              'pay_date' => $this->pay_date->timestamp ?? null,
              'number' => $this->number ?? null,
              'reference' => $this->reference ?? null,
              'currency_code' => $this->currency_code ?? null,
              'customer_currency_code' => $this->project->contact->customer->default_currency ?? null,
              'total_paid_amount' => ceiling($total_paid_amount, 2),
              'total_paid_amount_usd' => ceiling($total_paid_amount_usd, 2),
              'total_paid_vat' => ceiling($total_paid_vat, 2),
              'total_paid_vat_usd' => ceiling($total_paid_vat_usd, 2),
              'total_price' => ceiling($this->total_price, 2),
              'customer_total_price' => ceiling(get_total_price(Invoice::class, $this->id), 2),
              'total_vat' => ceiling($this->total_vat, 2),
              'total_price_usd' => ceiling($this->total_price_usd, 2),
              'total_vat_usd' => ceiling($this->total_vat_usd, 2),
              'credit_notes_total_price' => $this->creditNotes ?
                  $this->creditNotes->where('status', CreditNoteStatus::paid()->getIndex())->sum('total_price') : 0,
              'credit_notes_total_price_usd' => $this->creditNotes ?
                  $this->creditNotes->where('status', CreditNoteStatus::paid()->getIndex())->sum('total_price_usd') : 0,
              'credit_notes_total_vat' => $this->creditNotes ?
                  $this->creditNotes->where('status', CreditNoteStatus::paid()->getIndex())->sum('total_vat') : 0,
              'credit_notes_total_vat_usd' => $this->creditNotes ?
                  $this->creditNotes->where('status', CreditNoteStatus::paid()->getIndex())->sum('total_vat_usd') : 0,
              'created_at' => $this->created_at->timestamp,
              'updated_at' => $this->updated_at->timestamp ?? null,
              'download' => $this->hasMedia('invoice_uploads'),
              'legal_entity_id' => $this->legal_entity_id,
              'legal_entity' => $this->legalEntity->name ?? null,
              'sales_person_id' => $salesPersons ? $salesPersons->pluck('id') : null,
              'master' => (bool)$this->master,
              'shadow' => (bool)$this->shadow,
              'shadow_price' => ceiling($shadowPrice, 2),
              'shadow_price_usd' => ceiling($shadowPriceUsd, 2),
              'shadow_vat' => round($shadowVat, 2),
              'shadow_vat_usd' => round($shadowVatUsd, 2),
              'all_invoices_paid' => $allInvoicesPaid,
              'intra_company' => $intraCompany,
              'intra_company_id' => $intraCompanyId,
              'markup'                => $markup,
              'markup_usd'            => $markupUsd,
              'gross_margin'          => ceiling($budget - $invoiceTotalCost, 2),
              'gross_margin_usd'      => ceiling($budgetUsd - $invoiceTotalCostUsd, 2),
              'eligible_for_earnout'=> !$intraCompany || $this->eligible_for_earnout,
              'second_sales_person_id' => $leadGens ? $leadGens->pluck('id') : null,
              'details' => [
                  'columns' => [
                      [
                          'prop' => 'date',
                          'name' => 'date',
                          'type' => 'date'
                      ],
                      [
                          'prop' => 'status',
                          'name' => 'status',
                          'type' => 'enum',
                          'enum' => 'creditnotestatus'
                      ],
                      [
                          'prop' => 'type',
                          'name' => 'type',
                          'type' => 'enum',
                          'enum' => 'creditnotetype'
                      ],
                      [
                          'prop' => 'number',
                          'name' => 'number',
                          'type' => 'string'
                      ],
                      [
                          'prop' => 'total_price',
                          'name' => 'total price',
                          'type' => 'decimal'
                      ],
                      [
                          'prop' => 'total_vat',
                          'name' => 'total vat',
                          'type' => 'decimal'
                      ]
                  ],
                  'rows' => CreditNoteElastic::collection($this->creditNotes)
              ]
          ];
    }
}
