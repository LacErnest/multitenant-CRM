<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\MasterShadow;
use App\Models\Order;
use App\Services\ItemService;
use Illuminate\Support\Facades\App;
use Tenancy\Facades\Tenancy;

class EloquentInvoiceRepository extends EloquentRepository implements InvoiceRepositoryInterface
{
    public const CURRENT_MODEL = Invoice::class;

    public function createShadowInvoices(Order $order, Invoice $invoice, Company $company): void
    {
        $itemService = App::make(ItemService::class);
        $itemsArray = $invoice->items->groupBy('company_id')->sortBy('order');

        foreach ($itemsArray as $array) {
            if ($array[0]->company_id == $company->id) {
                continue;
            }
            $masterShadow = $order->shadows->where('shadow_company_id', $array[0]->company_id)->first();

            if ($masterShadow) {
                $shadowCompany = Company::find($masterShadow->shadow_company_id);
                $itemOrder = 0;
                Tenancy::setTenant($shadowCompany);
                $shadowOrder = Order::find($masterShadow->shadow_id);

                $shadowInvoice = $this->create([
                'created_by' => $invoice->created_by,
                'project_id' => $shadowOrder->project_id,
                'order_id' => $shadowOrder->id,
                'type' => InvoiceType::accrec()->getIndex(),
                'date' => $invoice->date,
                'due_date' => $invoice->due_date,
                'status' => InvoiceStatus::draft()->getIndex(),
                'number' => $invoice->number,
                'reference' => $invoice->reference,
                'currency_code' => (int)$invoice->currency_code,
                'currency_rate_company' => $invoice->currency_rate_company,
                'currency_rate_customer' => $invoice->currency_rate_customer,
                'total_price' => 0,
                'total_vat' => 0,
                'total_price_usd' => 0,
                'total_vat_usd' => 0,
                'manual_input' => $invoice->manual_input,
                'manual_price' => 0,
                'manual_vat' => 0,
                'legal_entity_id' => $order->legal_entity_id,
                'master' => false,
                'shadow' => true,
                'vat_status' => $invoice->vat_status,
                'vat_percentage' => $invoice->vat_percentage,
                ]);

                $array->each(function ($item) use ($shadowInvoice, $shadowCompany, $invoice, &$itemOrder, $company) {
                    $invoiceItem = $item->replicate();
                    $invoiceItem->entity_id = $shadowInvoice->id;
                    $invoiceItem->entity_type = Invoice::class;
                    $invoiceItem->order = $itemOrder;
                    $invoiceItem->unit_price = $this->convertUnitPrice(
                        $item->unit_price,
                        $invoice->currency_rate_company,
                        $company->currency_code,
                        $shadowCompany->currency_code,
                        $invoice->manual_input
                    );
                    $invoiceItem->master_item_id = $item->id;
                    $invoiceItem->save();
                    if (!empty($item->priceModifiers)) {
                        $item->priceModifiers->each(function ($modifier) use ($invoiceItem) {
                            $invoiceModifier = $modifier->replicate();
                            $invoiceModifier->entity_id = $invoiceItem->id;
                            $invoiceModifier->entity_type = Item::class;
                            $invoiceModifier->save();
                        });
                    }
                    $itemOrder += 1;
                });

                if (!empty($invoice->priceModifiers)) {
                        $invoice->priceModifiers->each(function ($item) use ($shadowInvoice) {
                            $invoiceModifier = $item->replicate();
                            $invoiceModifier->entity_id = $shadowInvoice->id;
                            $invoiceModifier->entity_type = Invoice::class;
                            $invoiceModifier->save();
                        });
                }

                $itemService->savePricesAndCurrencyRates($shadowCompany->id, $shadowInvoice->id, Invoice::class);

                MasterShadow::create([
                'master_id' => $invoice->id,
                'shadow_id' => $shadowInvoice->id,
                'master_company_id' => $company->id,
                'shadow_company_id' => $shadowCompany->id,
                ]);
            }
        }

        Tenancy::setTenant($company);
    }

    private function convertUnitPrice($price, $rate, $masterCurrency, $shadowCurrency, $manualInput)
    {
        if (!$manualInput && $masterCurrency != $shadowCurrency) {
            return $price * $rate;
        }

        return $price;
    }
}
