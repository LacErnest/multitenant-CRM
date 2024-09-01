<?php


namespace App\Repositories;

use App\Enums\CurrencyCode;
use App\Enums\EmployeeType;
use App\Enums\EntityPenaltyType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\VatStatus;
use App\Models\Address;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Resource;
use App\Models\Resource as ResourceModel;
use App\Models\Service;
use App\Services\ItemService;
use App\Services\ResourceService;
use App\Models\Setting;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class ResourceRepository
 *
 * @deprecated
 */
class ResourceRepository
{
    protected ResourceModel $resource;
    protected Address $address;

    public function __construct(ResourceModel $resource, Address $address)
    {
        $this->resource = $resource;
        $this->address = $address;
    }

    public function get($resource_id)
    {
        if (!$resource = $this->resource->find($resource_id)) {
            throw new ModelNotFoundException();
        }
        return $resource;
    }

    public function create(array $attributes)
    {
        if ($resource = $this->resource->create($attributes)) {
            $address = $this->address->create($attributes);
            $address->resources()->save($resource);
        }
        return $this->resource->find($resource->id);
    }

    public function update(array $attributes, $resource_id)
    {
        if (auth()->user() === null) {
            unset($attributes['hourly_rate']);
            unset($attributes['daily_rate']);
        }

        if (!$resource = $this->resource->find($resource_id)) {
            throw new ModelNotFoundException();
        }

        $resource->address->update($attributes);
        $resource->update($attributes);

        return $resource;
    }

    public function suggest($company_id, Request $request, $value)
    {
        return $this->autoGet($company_id, $request, $value);
    }

    private function autoGet($company_id, Request $request, $value = null)
    {
        $searchQuery = [];
        $typeQuery = [];
        $statusQuery = [];
        $status = [];
        $borrowedQuery = [];

        $type = !($request->input('type') === null) ? (int)$request->input('type') : null;
        if (!($request->input('status') === null)) {
            $input = str_replace('[', '', $request->input('status'));
            $input = str_replace(']', '', $input);
            $items = explode(',', $input);
            foreach ($items as $item) {
                array_push($status, $item);
            }
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
                                    'first_name' => [
                                        'value' => '*' . $value . '*'
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'last_name' => [
                                        'value' => '*' . $value . '*'
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'name.keyword' => [
                                        'value' => '*' . $value . '*'
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'first_name' => [
                                        'value' => $value . '*',
                                        'boost' => 5
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'last_name' => [
                                        'value' => $value . '*',
                                        'boost' => 5
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'name.keyword' => [
                                        'value' => $value . '*',
                                        'boost' => 5
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'first_name' => [
                                        'value' => $value,
                                        'boost' => 10
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'last_name' => [
                                        'value' => $value,
                                        'boost' => 10
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'name.keyword' => [
                                        'value' => $value,
                                        'boost' => 10
                                    ]
                                ]
                            ],
                        ]
                    ]
                ]
            );
        } else {
            $limit = null;
        }

        if (!($type === null)) {
            array_push($typeQuery, ['bool' => ['should' => [['match' => ['type' => $type]]]]]);
        }

        if (!empty($status)) {
            array_push($statusQuery, ['bool' => ['should' => [['terms' => ['status' => $status]]]]]);
        }

        array_push($borrowedQuery, [
          'bool' => [
              'should' => [
                  ['match' => ['company_id' => $company_id]],
                  ['match' => ['can_be_borrowed' => true]]
              ]
          ]
        ]);

        $query = [
          'query' => [
              'bool' => [
                  'must' => array_merge(
                      $searchQuery,
                      $typeQuery,
                      $statusQuery,
                      $borrowedQuery
                  )]
          ]
        ];

        $users = Resource::searchAllTenantsQuery('resources', $query, $limit);
        $result = $users['hits']['hits'];

        $typeQuery = [];
        $typeQuery[] = ['bool' => ['should' => [['match' => ['type' => EmployeeType::contractor()->getIndex()]]]]];
        $employeeQuery = [
          'query' => [
              'bool' => [
                  'must' => array_merge(
                      $searchQuery,
                      $typeQuery,
                      $statusQuery,
                      $borrowedQuery
                  )]
          ]
        ];
        $employees = Employee::searchAllTenantsQuery('employees', $employeeQuery, $limit);
        $employeeResult = $employees['hits']['hits'];
        $result = array_merge($result, $employeeResult);

        $result = array_map(function ($r) {
            $r =  Arr::only($r['_source'], [
              'id',
              'first_name',
              'last_name',
              'name',
              'status',
              'default_currency',
              'country',
              'overhead_employee',
              'non_vat_liable',
            ]);
            if ($r['first_name'] || $r['last_name']) {
                if (Arr::exists($r, 'overhead_employee')) {
                    $r['name'] = $r['first_name'] . ' ' . $r['last_name'] . ' (contractor)';
                    $r['non_vat_liable'] = false;
                } else {
                    $r['name'] = $r['first_name'] . ' ' . $r['last_name'] . ' (' . $r['name'] . ')';
                }
            }
            unset($r['first_name']);
            unset($r['last_name']);
            unset($r['overhead_employee']);
            return $r;
        }, $result);
        return response()->json($result);
    }

    public function suggestJobTitle($company_id, $value)
    {

        $searchQuery = [
          'bool' => [
              'should' => [
                  [
                      'wildcard' => [
                          'job_title.keyword' => [
                              'value' => '*' . $value . '*'
                          ]
                      ]
                  ],
                  [
                      'wildcard' => [
                          'job_title.keyword' => [
                              'value' => $value . '*',
                              'boost' => 5
                          ]
                      ]
                  ],
                  [
                      'wildcard' => [
                          'job_title.keyword' => [
                              'value' => $value,
                              'boost' => 10
                          ]
                      ]
                  ],
              ]
          ]
        ];

        $query = [
          'query' => [
              'bool' => [
                  'must' => $searchQuery
              ]
          ]
        ];

        $resources = Resource::searchBySingleQuery($query);

        foreach ($resources['hits']['hits'] as $hit) {
            ;
            $suggestions[] = $hit['_source']['job_title'];
        }
        return response()->json(['suggestions' => $suggestions ?? []]);
    }

    public function uploadInvoice(PurchaseOrder $purchaseOrder, $attributes)
    {
        $invoice = Invoice::where('purchase_order_id', $purchaseOrder->id)->first();

        if ($invoice && (InvoiceStatus::isAuthorised($invoice->status) || InvoiceStatus::isSubmitted($invoice->status)
              || InvoiceStatus::isPaid($invoice->status))) {
            throw new UnprocessableEntityHttpException("Invoice already accepted, you can't upload a new one.");
        }

        $company = Company::find(getTenantWithConnection());

        $manual_price = entityPrice(PurchaseOrder::class, $purchaseOrder->id);
        $manual_price = applyPenalty($manual_price, $purchaseOrder);
        $total_price = $manual_price * (1/$purchaseOrder->currency_rate_resource);
        $total_price_usd = $total_price * ($company->currency_code == CurrencyCode::USD()->getIndex() ?
        safeDivide(1, $purchaseOrder->currency_rate_company) : $purchaseOrder->currency_rate_company);

        $invoiceAttributes = [
          'project_id' => $purchaseOrder->project_id,
          'purchase_order_id' => $purchaseOrder->id,
          'order_id' => $purchaseOrder->project && $purchaseOrder->project->order ? $purchaseOrder->project->order->id : null,
          'reference' => $purchaseOrder->reference,
          'type' => InvoiceType::accpay()->getIndex(),
          'date' => now(),
          'due_date' => now()->addDays($purchaseOrder->payment_terms),
          'currency_code' => $purchaseOrder->currency_code,
          'total_price' => $total_price,
          'total_vat' => 0,
          'total_price_usd' => $total_price_usd,
          'total_vat_usd' => 0,
          'currency_rate_company' => $purchaseOrder->currency_rate_company,
          'currency_rate_customer' => $purchaseOrder->currency_rate_customer,
          'manual_input' => $purchaseOrder->manual_input,
          'manual_price' => $manual_price,
          'manual_vat' => 0,
          'vat_status' => $purchaseOrder->vat_status,
          'vat_percentage' => $purchaseOrder->vat_percentage,
        ];

        if ($invoice === null) {
            $isIntraCompany = isIntraCompany($purchaseOrder->project->contact->customer->id ?? null);
            $draftInvoices = Invoice::where('number', 'like', 'Draft%')->orderByDesc('number')->get();
            if ($draftInvoices->isNotEmpty()) {
                $lastDraft = $draftInvoices->first();
                $lastNumber = str_replace('X', '', explode('-', $lastDraft->number)[1]);
            } else {
                $lastNumber = 0;
            }
            $invoiceAttributes['number'] = transformFormat('DRAFT-XXXX', $lastNumber + 1);
            $invoiceAttributes['status'] = InvoiceStatus::draft()->getIndex();
            $invoiceAttributes['legal_entity_id'] = null;
            $invoiceAttributes['eligible_for_earnout'] = !$isIntraCompany;
            $invoice = Invoice::create($invoiceAttributes);

            if (!empty($purchaseOrder->items)) {
                $purchaseOrder->items->each(function ($item) use ($invoice) {
                    $invoiceItem = $item->replicate();
                    $invoiceItem->entity_id = $invoice->id;
                    $invoiceItem->entity_type = Invoice::class;
                    $invoiceItem->save();
                    if (!empty($item->priceModifiers)) {
                        $item->priceModifiers->each(function ($modifier) use ($invoiceItem) {
                            $invoiceModifier = $modifier->replicate();
                            $invoiceModifier->entity_id = $invoiceItem->id;
                            $invoiceModifier->entity_type = Item::class;
                            $invoiceModifier->save();
                        });
                    }
                });
            }

            if (!empty($purchaseOrder->priceModifiers)) {
                $modifiers = $purchaseOrder->priceModifiers;
                $modifiers->each(function ($item) use ($invoice) {
                    $invoiceModifier = $item->replicate();
                    $invoiceModifier->entity_id = $invoice->id;
                    $invoiceModifier->entity_type = Invoice::class;
                    $invoiceModifier->save();
                });
            }
        } else {
            if (InvoiceStatus::isRejected($invoice->status)) {
                $invoiceAttributes['status'] = InvoiceStatus::draft()->getIndex();
            }

            if ($invoice->legal_entity_id || VatStatus::isAlways($invoice->vat_status) || $purchaseOrder->resource->non_vat_liable) {
                $itemService = App::make(ItemService::class);
                $itemService->savePricesAndCurrencyRates($company->id, $invoice->id, Invoice::class);
            }

            $invoice->update($invoiceAttributes);
        }

        $purchaseOrder->status = PurchaseOrderStatus::billed()->getIndex();
        $purchaseOrder->save();

        $invoice->addMediaFromBase64($attributes['file'])->usingFileName($invoice->id . '.pdf')->toMediaCollection('invoice_uploads');

        return $purchaseOrder;
    }

    public function downloadInvoice(Invoice $invoice)
    {
        return response()->download($invoice->getFirstMedia('invoice_uploads')->getPath(), $invoice->id . '.pdf', ['Content-Type' => 'application/pdf'], 'inline');
    }

    public function createServices(string $resourceId, array $services): void
    {
        DB::beginTransaction();

        try {
            foreach ($services as $service) {
                Service::create(array_merge($service, [
                'resource_id' => $resourceId,
                ]));
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateService(string $resourceId, string $serviceId, array $attributes): Service
    {
        $service = Service::findOrFail($serviceId);

        if ($service->resource_id == $resourceId) {
            DB::beginTransaction();

            try {
                $service = tap($service)->update($attributes);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return $service;
        }
        throw new ModelNotFoundException();
    }
}
