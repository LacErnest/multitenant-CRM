<?php

namespace App\Http\Resources\Elastic;

use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\VatStatus;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class InvoicePaymentElastic extends JsonResource
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
        $allInvoicesPaid = false;
        $shadowPrice = 0;
        $shadowPriceUsd = 0;
        $shadowVat = 0;
        $shadowVatUsd = 0;

        $project = $this->invoice ? $this->invoice->project : null;
        $order = $this->invoice ? $this->invoice->order : null;

        if ($project) {
            $po = $project->purchaseOrders()->whereIn('status', [PurchaseOrderStatus::submitted()->getIndex(),
            PurchaseOrderStatus::authorised()->getIndex(), PurchaseOrderStatus::billed()->getIndex(),
            PurchaseOrderStatus::paid()->getIndex(), PurchaseOrderStatus::completed()->getIndex()])->get();
            $costs = $po->sum('total_price');
            $vat = $po->sum('total_vat');
            $costs_usd = $po->sum('total_price_usd');
            $vat_usd = $po->sum('total_vat_usd');

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
                } elseif ($project->contact->customer->non_vat_liable) {
                    $taxIsApplicable = true;
                } else {
                    if (!($this->legal_entity_id === null)) {
                        $taxIsApplicable = $project->contact->customer->billing_address->country == $this->legalEntity->address->country;
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
        }

        if ($order && InvoiceType::isAccrec($this->invoice->type)) {
            $invoices = $order->invoices()->where([
            ['type', InvoiceType::accrec()->getIndex()],
            ['status', '!=', InvoiceStatus::unpaid()->getIndex()],
            ['status', '!=', InvoiceStatus::cancelled()->getIndex()]
            ])->orderByDesc('updated_at')->get();
            if ($invoices->count() != 0 && $invoices->count() == $invoices->whereNotNull('pay_date')->count()) {
                $allInvoicesPaid = true;
                if ($this->invoice->id == $invoices->first()->id) {
                    $last_paid_invoice = true;
                }
            }
        }


        return
          [
              'id' => $this->id,
              'xero_id' => $this->xero_id ?? null,
              'project_info' => [
                  'id' => $project ? $project->id : null,
                  'po_cost' => $project ? ceiling($costs, 2) : 0,
                  'po_vat' => $project ? ceiling($vat, 2) : 0,
                  'employee_cost' => $project ? ceiling($project->employee_costs, 2) : 0,
                  'po_cost_usd' => $project ? ceiling($costs_usd, 2) : 0,
                  'po_vat_usd' => $project ? ceiling($vat_usd, 2) : 0,
                  'employee_cost_usd' => $project ? ceiling($project->employee_costs_usd, 2) : 0,
                  'external_employee_cost' => $project ? ceiling($project->external_employee_costs, 2) : 0,
                  'external_employee_cost_usd' => $project ? ceiling($project->external_employee_costs_usd, 2) : 0,
              ],
              'project' => $project ? $project->name : null,
              'project_id' => $project ? $project->id : null,
              'invoice' => $this->invoice ? $this->invoice->number : null,
              'invoice_id' => $this->invoice ? $this->invoice->id : null,
              'invoice_info'=>[
                  'id' => $this->id,
                  'is_last_paid_invoice' => $order ? $last_paid_invoice : false,
                  'purchase_order' => $this->purchaseOrder ? $this->purchaseOrder->number : null,
                  'purchase_order_id' => $this->purchase_order_id ?? null,
                  'type' => $this->type ?? null,
                  'date' => $this->date->timestamp ?? null,
                  'due_date' => $this->due_date->timestamp ?? null,
                  'status' => $this->status ?? null,
                  'pay_date' => $this->pay_date->timestamp ?? null,
                  'number' => $this->number ?? null,
                  'reference' => $this->reference ?? null,
                  'total_price' => ceiling($this->total_price, 2),
                  'total_vat' => ceiling($this->total_vat, 2),
                  'total_price_usd' => ceiling($this->total_price_usd, 2),
                  'total_vat_usd' => ceiling($this->total_vat_usd, 2),
                  'all_invoices_paid' => $allInvoicesPaid,
              ],
              'order' => $order ? $order->number : null,
              'order_id' => $order ? $order->id : null,
              'order_status' => $order ? $order->status : null,
              'pay_date' => $this->pay_date->timestamp ?? null,
              'number' => $this->number ?? null,
              'currency_code' => $this->currency_code ?? null,
              'pay_amount' => $this->pay_amount,
              'created_at' => $this->created_at->timestamp,
              'updated_at' => $this->updated_at->timestamp ?? null,

          ];
    }
}
