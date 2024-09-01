<?php


namespace App\Repositories;


use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Enums\CustomerStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Order;
use App\Models\Project;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

/**
 * Class OrderRepository
 *
 * @deprecated
 */
class OrderRepository
{
    protected Order $order;

    public function __construct(
        Order $order
    ) {
        $this->order = $order;
    }

    public function create($project_id, $attributes)
    {
        $attributes['project_id'] = $project_id;
        if (!array_key_exists('status', $attributes)) {
            $attributes['status'] = 0;
        }
        $format = Setting::first();
        $attributes['number'] = transformFormat($format->order_number_format, $format->order_number + 1);
        $order = $this->order->create($attributes);

        if (key_exists('project_manager_id', $attributes)) {
            $order->project->project_manager_id = $attributes['project_manager_id'];
            $order->project->save();
        }

        $format->order_number += 1;
        $format->save();

        if ($order->project->contact->customer->status != CustomerStatus::active()->getIndex()) {
            $order->project->contact->customer->status = CustomerStatus::active()->getIndex();
            $order->project->contact->customer->save();
        }
        return $order;
    }

    public function changeOrderStatus(Order $order, $projectId, $payload): array
    {
        $company = Company::find(getTenantWithConnection());
        $isUSDCurrency = $company->currency_code === CurrencyCode::USD()->getIndex();

        $order_total = $isUSDCurrency ? $order->total_price_usd : $order->total_price;

        $payloadStatusDelivered = $this->payloadStatus($payload);

        if (Arr::exists($payload, 'need_invoice') && !$payload['need_invoice']) {
            return $payloadStatusDelivered;
        }

        $invoices = $order->invoices()->where([
          ['type', InvoiceType::accrec()->getIndex()],
          ['status', '!=', InvoiceStatus::cancelled()->getIndex()]
        ])->get();

        if ($invoices->isNotEmpty()) {
            $invoice_total = $isUSDCurrency ? $invoices->sum('total_price_usd') : $invoices->sum('total_price');

            if ($invoice_total >= $order_total) {
                return $payloadStatusDelivered;
            }

            if ($invoice_total < $order_total) {
                $invoice = $this->createInvoiceWithNotInvoicedItems($order, $invoices, $projectId, $isUSDCurrency);

                if ($invoice->master) {
                    $invoiceRepository = App::make(InvoiceRepositoryInterface::class);
                    $invoiceRepository->createShadowInvoices($order, $invoice->load('priceModifiers'), $company);
                }

                return $payloadStatusDelivered;
            }
        } else {
            $invoice = $this->createInvoice($projectId, $order);

            if ($invoice->master) {
                $invoiceRepository = App::make(InvoiceRepositoryInterface::class);
                $invoiceRepository->createShadowInvoices($order, $invoice->load('priceModifiers'), $company);
            }

            return $payloadStatusDelivered;
        }

        return $payload;
    }
    public function createInvoiceWithNotInvoicedItems($order, $invoices, $projectId, $isUSDCurrency)
    {
        $itemCompareData = [];
        if ($order->items->isNotEmpty()) {
            $order->items->each(function ($item) use (&$itemCompareData) {
                $itemCompareData[] = [
                'service_name' => $item->service_name,
                'quantity' => $item->quantity,
                ];
            });
        }

      // get items that are missing / not yet invoiced or invoiced but with different quantity.
        $newItemCompareData = $itemCompareData;
        $invoices->each(function ($invoice) use (&$newItemCompareData) {
            $invoice->items->each(function ($item) use (&$newItemCompareData) {

                foreach ($newItemCompareData as $key => $value) {
                    if ($value['service_name'] === $item->service_name) {
                        $quantity = $value['quantity'] - $item->quantity;
                        if ($quantity > 0) {
                            $newItemCompareData[$key]['quantity'] = $quantity;
                        } else {
                            unset($newItemCompareData[$key]);
                            $newItemCompareData = array_values($newItemCompareData);
                        }
                    }
                }
            });
        });

        // create invoice similar to function createInvoice will refactor duplicates later
        $draftInvoices = Invoice::where('number', 'like', 'Draft%')->orderByDesc('number')->get();

        if ($draftInvoices->isNotEmpty()) {
            $lastDraft = $draftInvoices->first();
            $lastNumber = str_replace('X', '', explode('-', $lastDraft->number)[1]);
        } else {
            $lastNumber = 0;
        }

        $isIntraCompany = isIntraCompany($order->project->contact->customer->id ?? null);

        $invoice = Invoice::create([
          'created_by' => auth()->user()->id,
          'project_id' => $projectId,
          'order_id' => $order->id,
          'type' => InvoiceType::accrec()->getIndex(),
          'date' => now(),
          'due_date' => Carbon::now()->addMonth(),
          'status' => InvoiceStatus::draft()->getIndex(),
          'number' => transformFormat('DRAFT-XXXX', $lastNumber + 1),
          'reference' => $order->reference,
          'currency_code' => (int)$order->currency_code,
          'currency_rate_company' => $order->currency_rate_company,
          'currency_rate_customer' => $order->currency_rate_customer,
          'total_price' => 0,
          'total_vat' => 0,
          'total_price_usd' => 0,
          'total_vat_usd' => 0,
          'manual_input' => $order->manual_input,
          'manual_price' => 0,
          'manual_vat' => 0,
          'legal_entity_id' => null,
          'master' => (bool)$order->master,
          'shadow' => (bool)$order->shadow,
          'vat_status' => $order->vat_status,
          'vat_percentage' => $order->vat_percentage,
          'eligible_for_earnout'=> !$isIntraCompany,
        ]);

        $order->items->each(function ($item) use ($newItemCompareData, $invoice) {
            foreach ($newItemCompareData as $value) {
                if ($value['service_name'] === $item->service_name) {
                    $invoiceItem = $item->replicate();
                    $invoiceItem->entity_id = $invoice->id;
                    $invoiceItem->entity_type = Invoice::class;
                    $invoiceItem->quantity = $value['quantity'];
                    $invoiceItem->save();
                    if (!empty($item->priceModifiers)) {
                        $item->priceModifiers->each(function ($modifier) use ($invoiceItem) {
                            $orderModifier = $modifier->replicate();
                            $orderModifier->entity_id = $invoiceItem->id;
                            $orderModifier->entity_type = Item::class;
                            $orderModifier->save();
                        });
                    }
                }
            }
        });

        if (!empty($order->priceModifiers)) {
            $order->priceModifiers->each(function ($item) use ($invoice) {
                $orderModifier = $item->replicate();
                $orderModifier->entity_id = $invoice->id;
                $orderModifier->entity_type = Invoice::class;
                $orderModifier->save();
            });
        }

        if ($order->manual_input) {
            $manual_price = entityPrice(Invoice::class, $invoice->id);
            $total_price = $manual_price * (!$isUSDCurrency ?
            safeDivide(1, $invoice->currency_rate_customer) : $invoice->currency_rate_company * safeDivide(1, $invoice->currency_rate_customer));
            $total_price_usd = $total_price * ($isUSDCurrency ?
            safeDivide(1, $invoice->currency_rate_company) : $invoice->currency_rate_company);
        } else {
            if ($isUSDCurrency) {
                $total_price_usd = entityPrice(Invoice::class, $invoice->id);
                $total_price = $total_price_usd * $invoice->currency_rate_company;
                $manual_price = 0;
            } else {
                $total_price = entityPrice(Invoice::class, $invoice->id);
                $total_price_usd = $total_price * $invoice->currency_rate_company;
                $manual_price = 0;
            }
        }

        $invoice->total_price = $total_price;
        $invoice->total_price_usd = $total_price_usd;
        $invoice->manual_price = $manual_price;
        $invoice->save();

        return $invoice;
    }

    public function payloadStatus($payload): array
    {
        Arr::set($payload, 'delivered_at', Carbon::now());
        $payload['status'] = OrderStatus::invoiced()->getIndex();

        return $payload;
    }

    public function createInvoice($projectId, $order)
    {
        $company = Company::find(getTenantWithConnection());
        $draftInvoices = Invoice::where('number', 'like', 'Draft%')->orderByDesc('number')->get();
        $project  = Project::find($projectId);
        $paymentTerms = null;

        if ($draftInvoices->isNotEmpty()) {
            $lastDraft = $draftInvoices->first();
            $lastNumber = str_replace('X', '', explode('-', $lastDraft->number)[1]);
        } else {
            $lastNumber = 0;
        }

        if ($order->manual_input) {
            $manual_price = entityPrice(Order::class, $order->id);
            $total_price = $manual_price * ($company->currency_code == CurrencyCode::EUR()->getIndex() ?
            safeDivide(1, $order->currency_rate_customer) : $order->currency_rate_company * safeDivide(1, $order->currency_rate_customer));
            $total_price_usd = $total_price * ($company->currency_code == CurrencyCode::USD()->getIndex() ?
            safeDivide(1, $order->currency_rate_company) : $order->currency_rate_company);
        } else {
            if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
                $total_price_usd = entityPrice(Order::class, $order->id);
                $total_price = $total_price_usd * $order->currency_rate_company;
            } else {
                $total_price = entityPrice(Order::class, $order->id);
                $total_price_usd = $total_price * $order->currency_rate_company;
            }
        }

        if (!empty($project->contact->customer->payment_due_date)) {
            $currentDate = Carbon::now();
            $paymentTerms = $currentDate->addDays($project->contact->customer->payment_due_date);
        }

        $isIntraCompany = isIntraCompany($order->project->contact->customer->id ?? null);

        $invoice = Invoice::create([
          'created_by' => auth()->user()->id,
          'project_id' => $projectId,
          'order_id' => $order->id,
          'type' => InvoiceType::accrec()->getIndex(),
          'date' => now(),
          'due_date' => !empty($paymentTerms) ? $paymentTerms : Carbon::now()->addMonth(),
          'status' => InvoiceStatus::draft()->getIndex(),
          'number' => transformFormat('DRAFT-XXXX', $lastNumber + 1),
          'reference' => $order->reference,
          'currency_code' => (int)$order->currency_code,
          'currency_rate_company' => $order->currency_rate_company,
          'currency_rate_customer' => $order->currency_rate_customer,
          'total_price' => $total_price,
          'total_vat' => 0,
          'total_price_usd' => $total_price_usd,
          'payment_terms' => $paymentTerms,
          'total_vat_usd' => 0,
          'manual_input' => $order->manual_input,
          'manual_price' => $order->manual_input ? $manual_price : $order->manual_price,
          'manual_vat' => 0,
          'legal_entity_id' => null,
          'master' => (bool)$order->master,
          'shadow' => (bool)$order->shadow,
          'vat_status' => $order->vat_status,
          'vat_percentage' => $order->vat_percentage,
          'eligible_for_earnout'=> !$isIntraCompany,
        ]);

        if (!empty($order->items)) {
            $order->items->each(function ($item) use ($invoice) {
                $invoiceItem = $item->replicate();
                $invoiceItem->entity_id = $invoice->id;
                $invoiceItem->entity_type = Invoice::class;
                $invoiceItem->save();
                if (!empty($item->priceModifiers)) {
                    $item->priceModifiers->each(function ($modifier) use ($invoiceItem) {
                        $orderModifier = $modifier->replicate();
                        $orderModifier->entity_id = $invoiceItem->id;
                        $orderModifier->entity_type = Item::class;
                        $orderModifier->save();
                    });
                }
            });
        }

        if (!empty($order->priceModifiers)) {
            $order->priceModifiers->each(function ($item) use ($invoice) {
                $orderModifier = $item->replicate();
                $orderModifier->entity_id = $invoice->id;
                $orderModifier->entity_type = Invoice::class;
                $orderModifier->save();
            });
        }

        return $invoice;
    }

    public function suggest(string $companyId, string $value, bool $allOrders)
    {
        $searchQuery = [];
        $sortQuery = [];
        $termsQuery = [];
        $restrictedQuery = [];

        if (UserRole::isAdmin(auth()->user()->role) || Company::find($companyId)->currency_code == CurrencyCode::EUR()->getIndex()) {
            $price = 'total_price';
            $symbol = '€';
        } else {
            $price = 'total_price_usd';
            $symbol = '$';
        }

        if (UserRole::isPm_restricted(auth()->user()->role)) {
            array_push($restrictedQuery, [
            'bool' => [
                'must' => [
                    ['match' => ['project_manager_id' => auth()->user()->id]]
                ]
            ]
            ]);
        }

        if (!($value === null)) {
            $limit = 5;
            $query_array = explode(' ', str_replace(['"', '+', '=', '-', '&&', '||', '>', '<', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '\\', '/'], '?', trim($value)));
            $query_array = array_filter($query_array);
            $query_string = implode(' ', $query_array);
            $value = strtolower($query_string);

            array_push(
                $searchQuery,
                [
                  'bool' => [
                      'should' => [
                          [
                              'wildcard' => [
                                  'number' => [
                                      'value' => '*' . $value . '*'
                                  ]
                              ]
                          ],
                          [
                              'wildcard' => [
                                  'number' => [
                                      'value' => $value . '*',
                                      'boost' => 5
                                  ]
                              ]
                          ],
                          [
                              'wildcard' => [
                                  'number' => [
                                      'value' => '*' . $value,
                                      'boost' => 10
                                  ]
                              ]
                          ],
                          [
                              'wildcard' => [
                                  'number' => [
                                      'value' => $value,
                                      'boost' => 15
                                  ]
                              ]
                          ],
                      ]
                  ]
                ]
            );
        } else {
            $limit = null;
            $sortQuery = ['date' => ['order' => 'desc']];
        }

        if ($allOrders) {
            array_push($termsQuery, [
            'bool' => [
                'must' => [
                    ['terms' => ['status' => [OrderStatus::active()->getIndex(),
                        OrderStatus::delivered()->getIndex(), OrderStatus::invoiced()->getIndex()]]]
                ]
            ]
            ]);
        } else {
            array_push($termsQuery, [
            'bool' => [
                'must' => [
                    ['terms' => ['status' => [OrderStatus::active()->getIndex()]]]
                ]
            ]
            ]);
        }

        $query = [
          'query' => [
              'bool' => [
                  'must' => array_merge(
                      $searchQuery,
                      $termsQuery,
                      $restrictedQuery
                  )]
          ],
          'sort' => $sortQuery
        ];

        $orders = Order::searchBySingleQuery($query, $limit);

        $result = $orders['hits']['hits'];

        $result = array_map(function ($r) use ($allOrders, $price, $symbol) {
            if ($allOrders) {
                $extra = $r['_source']['customer'] . ': ' . $symbol . $r['_source'][$price];
                $r =  Arr::only($r['_source'], ['id', 'number']);
                $r['number'] = $r['number'] . ' (' . $extra . ')';
            } else {
                $r =  Arr::only($r['_source'], ['id', 'number']);
            }

            return $r;
        }, $result);
        return response()->json($result);
    }
}
