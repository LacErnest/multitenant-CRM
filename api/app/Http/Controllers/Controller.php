<?php

namespace App\Http\Controllers;

use App\Enums\CurrencyCode;
use App\Enums\EntityPenaltyType;
use App\Enums\InvoiceType;
use App\Enums\TablePreferenceType;
use App\Enums\UserRole;
use App\Http\Requests\Item\ItemCreateRequest;
use App\Http\Requests\Item\ItemDeleteRequest;
use App\Http\Requests\Item\ItemUpdateRequest;
use App\Http\Requests\PriceModifier\PriceModifierCreateRequest;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Item\ModifierResource;
use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Employee;
use App\Models\EmployeeHistory;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LegalEntity;
use App\Models\Order;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Resource;
use App\Models\ResourceInvoice;
use App\Models\Service;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\EmailTemplateService;
use App\Services\ItemService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Tenancy\Facades\Tenancy;
use Illuminate\Support\Facades\App;
use App\Traits\Models\UpdateSalesPersonRelationship;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, UpdateSalesPersonRelationship;

    protected string $model;

    protected ItemService $itemService;

    /**
     * @var EmailTemplateService
     */
    protected EmailTemplateService $emailTemplateService;

    public function __construct(ItemService $itemService)
    {
        $this->itemService = $itemService;
        $this->emailTemplateService = app(EmailTemplateService::class);
    }

    public function getAll(string $companyId, Request $request, int $entity): array
    {
        $page = $request->input('page', 0);
        $amount = $request->input('amount', 10);
        $key = $request->input('key', 'users');
        $resourceID = $request->input('resource', null);
        $projectID = $request->input('project', null);
        $employeeID = $request->input('employee', null);
        $invoiceID = $request->input('invoice', null);
        $tableConfig = $this->getTableConfig($entity, $key);
        return $this->getResults($this->model, $companyId, $tableConfig, $page, $amount, $resourceID, $projectID, $employeeID, $invoiceID);
    }

    public function getSingle($company_id, $entity_id)
    {
        if (Tenancy::isIdentified()) {
            if ($this->model == Project::class) {
                $model = $this->model::with('quotes', 'order', 'invoices', 'resourceInvoices', 'contact', 'projectManager', 'purchaseOrders')->findOrFail($entity_id);
            } else {
                $model = $this->model::findOrFail($entity_id);
            }
            return $this->singleResourceClass()::make($model);
        } else {
            return null;
        }
    }

    public function getSingleFromProject($company_id, $project_id, $entity_id)
    {
        $model = $this->model::where('project_id', $project_id)->find($entity_id);
        if ($model) {
            return $this->singleResourceClass()::make($model);
        } else {
            throw new ModelNotFoundException();
        }
    }

    public function createItem(
        string $companyId,
        string $projectId,
        string $entityId,
        ItemCreateRequest $request
    ): ItemResource {
        $item = $this->itemService->createItem($companyId, $entityId, $request->allSafe(), $this->model);

        return ItemResource::make($item->load('priceModifiers'));
    }

    public function updateItem(
        string $companyId,
        string $projectId,
        string $entityId,
        string $itemId,
        ItemUpdateRequest $request
    ): ItemResource {
        $item = $this->itemService->updateItem($companyId, $entityId, $itemId, $request->allSafe(), $this->model);

        return ItemResource::make($item->load('priceModifiers'));
    }

    public function deleteItems(
        string $companyId,
        string $projectId,
        string $entityId,
        ItemDeleteRequest $request
    ): Response {
        $this->itemService->deleteItems($companyId, $entityId, $request->input('items'), $this->model);

        return response()->noContent();
    }

    public function createPriceModifier(
        string $companyId,
        string $projectId,
        string $entityId,
        PriceModifierCreateRequest $request
    ): ModifierResource {
        $modifier = $this->itemService->createPriceModifier($companyId, $entityId, $request->allSafe(), $this->model);

        return ModifierResource::make($modifier);
    }

    public function updatePriceModifier(
        string $companyId,
        string $projectId,
        string $entityId,
        string $priceModifierId,
        PriceModifierCreateRequest $request
    ): ModifierResource {
        $modifier = $this->itemService->updatePriceModifier($companyId, $entityId, $priceModifierId, $request->allSafe(), $this->model);

        return ModifierResource::make($modifier);
    }

    public function deletePriceModifier(
        string $companyId,
        string $projectId,
        string $entityId,
        string $priceModifierId
    ): Response {
        $this->itemService->deletePriceModifier($companyId, $entityId, $priceModifierId, $this->model);

        return response()->noContent();
    }

    public function cloneEntity($company_id, $project_id, $entity_id, $number, $destination_id, $entity_class, $orderId = null)
    {
        $entity = $this->model::with('items', 'items.priceModifiers', 'priceModifiers')->findOrFail($entity_id);
        $clone = $entity->replicate();
        $clone->project_id = $destination_id;
        $clone->status = 0;
        $clone->number = $number;

        if ($this->model === Invoice::class || $this->model === PurchaseOrder::class) {
            $clone->legal_entity_id = null;
            $clone->pay_date = null;
            if (!($orderId === null)) {
                $clone->order_id = $orderId;
            }
        }
        if ($this->model === PurchaseOrder::class) {
            $clone->penalty = null;
            $clone->penalty_type = null;
            $clone->reason_of_penalty = null;
            $clone->reason_of_rejection = null;
            $clone->rating = null;
            $clone->reason = null;
        }
        if ($this->model === Invoice::class) {
            $project = Project::find($clone->project_id);
            $isIntraCompany = isIntraCompany($project->contact->customer->id ?? null);
            $clone->eligible_for_earnout = !$isIntraCompany;
            $clone->submitted_date = null;
            $clone->customer_notified_at = null;
            if (empty($clone->email_template_id)) {
                $this->emailTemplateService->setDefaultEmailTemplate($clone);
            }
        }
        if (in_array($this->model, [Quote::class, Invoice::class, Order::class, PurchaseOrder::class])) {
            $company = Company::find($company_id);
            $customerCurrency = CurrencyCode::make($clone->currency_code)->__toString();
            $currencyRates = getCurrencyRates();
            $currencyRateEurToUSD = $currencyRates['rates']['USD'];
            if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
                if (!$clone->currency_rate_company) {
                    $clone->currency_rate_company = safeDivide(1, $currencyRateEurToUSD);
                }
                if (!$clone->currency_rate_customer) {
                    $clone->currency_rate_customer = $clone->currency_rate_company * $currencyRates['rates'][$customerCurrency];
                }
            } else {
                if (!$clone->currency_rate_company) {
                    $clone->currency_rate_company = $currencyRateEurToUSD;
                }
                if (!$clone->currency_rate_customer) {
                    $clone->currency_rate_customer = $currencyRates['rates'][$customerCurrency];
                }
            }
        }

        $clone->save();

        if ($this->model === Quote::class) {
            $project = Project::find($clone->project_id);
            $clone->salesPersons()->attach($project->salesPersons);
        }

        foreach ($entity->getRelations() as $relation => $items) {
            foreach ($items as $item) {
                unset($item->id);
                $new_item = $clone->{$relation}()->create($item->toArray());
                foreach ($item->getRelations() as $deepRelation => $deepItems) {
                    foreach ($deepItems as $deepItem) {
                        unset($deepItem->id);
                        $new_item->{$deepRelation}()->create($deepItem->toArray());
                    }
                }
            }
        }

        return $this->singleResourceClass()::make($clone->refresh());
    }

    protected function getResults(
        string $class,
        string $company_id,
        array $config,
        int $page,
        int $amount,
        ?string $resourceId,
        ?string $projectID,
        ?string $employeeId,
        ?string $invoiceId
    ): array {
        $rates = getCurrencyRates();
        if (
            UserRole::isAdmin(auth()->user()->role) ||
            Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex()
        ) {
            $inDollar = false;
        } else {
            $inDollar = true;
        }

        $entities = array(Quote::class, Order::class, Invoice::class, PurchaseOrder::class, ResourceInvoice::class, InvoicePayment::class);
        $query = ['bool' => ['filter' => []]];

        if ($class == User::class && !UserRole::isAdmin(auth()->user()->role)) {
            $query = [
                'bool' => [
                    'must' => [
                        ['match' => ['company_id' => $company_id]]
                    ],
                    'must_not' => [
                        ['match' => ['role' => UserRole::admin()->getIndex()]]
                    ]
                ]
            ];
        } elseif ($class == User::class && UserRole::isAdmin(auth()->user()->role)) {
            $query = [
                'bool' => [
                    'should' => [
                        ['match' => ['company_id' => $company_id]],
                        ['bool' => [
                            'must_not' => [
                                ['exists' => ['field' => 'company_id']]
                            ]
                        ]]
                    ],
                    'minimum_should_match' => 1
                ]
            ];
        }
        if ($class == Invoice::class) {
            $query = [
                'bool' => [
                    'must' => [
                        ['match' => ['type' => InvoiceType::accrec()->getIndex()]],
                        ['bool' => [
                            'must_not' => [
                                ['match' => ['shadow' => true]]
                            ]
                        ]]
                    ]
                ]
            ];
        }
        if ($class == ResourceInvoice::class) {
            $class = Invoice::class;
            if ($projectID) {
                $query = [
                    'bool' => [
                        'must' => [
                            ['bool' => [
                                'must' => [
                                    'exists' => [
                                        'field' => 'purchase_order_id'
                                    ]
                                ]
                            ]]
                        ]
                    ]
                ];
            } else {
                $query = [
                    'bool' => [
                        'must' => [
                            'exists' => [
                                'field' => 'purchase_order_id'
                            ]
                        ]
                    ]
                ];
            }
        }
        if ($class == InvoicePayment::class) {
            if ($invoiceId) {
                $query = [
                    'bool' => [
                        'must' => [
                            ['match' => ['invoice_id' => $invoiceId]],
                            ['bool' => [
                                'must' => [
                                    'exists' => [
                                        'field' => 'invoice_id'
                                    ]
                                ]
                            ]]
                        ]
                    ]
                ];
            } else {
                $query = [
                    'bool' => [
                        'must' => [
                            ['bool' => [
                                'must' => [
                                    'exists' => [
                                        'field' => 'invoice_id'
                                    ]
                                ]
                            ]]
                        ]
                    ]
                ];
            }
        }
        if ($class == Service::class) {
            if ($resourceId) {
                $query = [
                    'bool' => [
                        'must' => [
                            ['match' => ['resource_id' => $resourceId]],
                            ['bool' => [
                                'must_not' => [
                                    ['exists' => ['field' => 'deleted_at']]
                                ]
                            ]]
                        ]
                    ]
                ];
            } else {
                $query = [
                    'bool' => [
                        'must' => [
                            [
                                'bool' => [
                                    'must_not' => [
                                        'exists' => [
                                            'field' => 'deleted_at'
                                        ],
                                    ]
                                ],
                            ],
                            [
                                'bool' => [
                                    'must_not' => [
                                        'exists' => [
                                            'field' => 'resource_id'
                                        ],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
            }
        }
        if ($class == Employee::class && (UserRole::isPm(auth()->user()->role))) {
            $query = [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'overhead_employee' => [
                                    'query' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        if ($class == Quote::class) {
            $query = [
                'bool' => [
                    'must_not' => [
                        ['match' => ['shadow' => true]]
                    ]
                ]
            ];
        }

        if ($projectID) {
            $query = array_merge_recursive($query, [
                'bool' => [
                    'must' => [
                        ['match' => ['project_id' => $projectID]]
                    ],
                ]
            ]);
        }

        if ($class == EmployeeHistory::class) {
            $query = [
                'bool' => [
                    'must' => [
                        ['match' => ['employee_id' => $employeeId]]
                    ]
                ]
            ];
        }

        if (UserRole::isPm_restricted(auth()->user()->role) && in_array($class, $entities)) {
            $restrictedProjectIds = Project::where('project_manager_id', auth()->user()->id)->pluck('id')->toArray();

            $query['bool']['filter'][] = [
                'bool' => [
                    'must' => [
                        ['terms' => ['project_id' => $restrictedProjectIds]],
                    ]
                ]
            ];
        }

        if (UserRole::isSales(auth()->user()->role) && in_array($class, $entities)) {
            $salesIds = User::where('email', auth()->user()->email)->pluck('id')->toArray();

            // Retrieve project IDs through the new relationships
            $restrictedProjectIds = Project::whereHas('salesPersons', function ($query) use ($salesIds) {
                $query->whereIn('user_id', $salesIds);
            })->orWhereHas('leadGens', function ($query) use ($salesIds) {
                $query->whereIn('user_id', $salesIds);
            })->pluck('id')->toArray();

            $query['bool']['filter'][] = [
                'bool' => [
                    'must' => [
                        ['terms' => ['project_id' => $restrictedProjectIds]],
                    ]
                ]
            ];
        }

        /*if (empty($projectID) && !isColumnFiltered($config['filters'], 'intra_company') &&
        in_array($class, [Order::class, Invoice::class, Quote::class, PurchaseOrder::class])) {
        $query['bool']['filter'][] = [
            'bool' => [
                'must' => [
                    ['terms' => ['intra_company' => [false]]],
                ]
            ]
        ];
      }*/

        $sort = [];

        if ($config['filters']) {
            foreach ($config['filters'] as $filter) {
                switch ($filter['type']) {
                    case 'string':
                        $search = '';
                        foreach ($filter['value'] as $value) {
                            $search .= '*' . $value . '*';
                            $search = $search . ' OR ';
                        }
                        $search = substr($search, 0, -4);


                        $query['bool']['filter'][] = [
                            'query_string' => [
                                'fields' => [$filter['prop'], $filter['prop'] . '.keyword'],
                                'query' => $search
                            ],
                        ];
                        break;
                    case 'enum':
                    case 'uuid':
                        if (array_key_exists('cast', $filter) && $filter['cast'] !== 'boolean') {
                            $search = '';
                            foreach ($filter['value'] as $value) {
                                $search .= '*' . $value . '*';
                                $search = $search . ' OR ';
                            }
                            $search = substr($search, 0, -4);

                            $query['bool']['filter'][] = [
                                'query_string' => [
                                    'fields' => [$filter['cast'], $filter['cast'] . '.keyword'],
                                    'query' => $search
                                ],
                            ];
                        } else {
                            if (!empty($filter['cast']) && $filter['cast'] == 'boolean') {
                                $filter['value'] = filterBooleanFormat($filter['value']);
                            } else {
                                $filter['value'] = filterUuidFormat($filter['value']);
                            }
                            $query['bool']['filter'][] = [
                                'terms' => [$filter['prop'] => $filter['value']],
                            ];
                        }
                        break;
                    case 'percentage':
                    case 'integer':
                        $rangeQuery = [];
                        foreach ($filter['value'] as $value) {
                            if (!array_key_exists('from', $value) || empty($value['from'])) {
                                $value['from'] = null;
                            }
                            if (!array_key_exists('to', $value) || empty($value['to'])) {
                                $value['to'] = null;
                            }
                            $rangeQuery[] = [
                                'range' => [
                                    $filter['prop'] => [
                                        'gte' => $value['from'],
                                        'lte' => $value['to'],
                                    ],
                                ],
                            ];
                        }
                        $query['bool']['filter'][] = ['bool' => [
                            'should' => $rangeQuery
                        ]];
                        break;
                    case 'decimal':
                        $rangeQuery = [];
                        foreach ($filter['value'] as $value) {
                            if (!array_key_exists('min', $value) || empty($value['min'])) {
                                $value['min'] = null;
                            }
                            if (!array_key_exists('max', $value) || empty($value['max'])) {
                                $value['max'] = null;
                            }
                            $rangeQuery[] = [
                                'range' => [
                                    $filter['prop'] => [
                                        'gte' => $value['min'],
                                        'lte' => $value['max'],
                                    ],
                                ],
                            ];
                        }
                        $query['bool']['filter'][] = ['bool' => [
                            'should' => $rangeQuery
                        ]];
                        break;
                    case 'date':
                        $rangeQuery = [];
                        if (!array_key_exists('0', $filter['value']) || empty($filter['value'][0])) {
                            $filter['value'][0] = '1970';
                        }
                        if (!array_key_exists('1', $filter['value']) || empty($filter['value'][1])) {
                            $filter['value'][1] = now()->format('Y-m-d');
                        }
                        if (!empty($filter['should'])) {
                            $should = [];
                            foreach ($filter['should'] as $option) {
                                $musts = [];
                                if ($option['prop'] == 'date') {
                                    $musts[] = ['range' => ['date' => [
                                        'gte' => Date::parse($filter['value'][0]),
                                        'lte' => Date::parse($filter['value'][1]),
                                        'format' => 'date_time',
                                    ]]];
                                } elseif ($option['prop'] == 'created_at') {
                                    $musts[] = ['range' => ['created_at' => [
                                        'lte' => Date::parse($filter['value'][1]),
                                        'format' => 'date_time',
                                    ]]];
                                } elseif ($option['prop'] == 'pay_date') {
                                    $musts[] = ['range' => ['pay_date' => [
                                        'gte' => Date::parse($filter['value'][0]),
                                        'lte' => Date::parse($filter['value'][1]),
                                        'format' => 'date_time',
                                    ]]];
                                } elseif ($option['prop'] == 'delivered_at') {
                                    $musts[] = ['range' => ['delivered_at' => [
                                        'lte' => Date::parse($filter['value'][1]),
                                        'format' => 'date_time',
                                    ]]];
                                }

                                foreach ($option['cond'] as $prop => $value) {
                                    $musts[] = ['terms' => [$prop => $value]];
                                }

                                $musts = [
                                    'bool' => [
                                        'must' => $musts
                                    ]
                                ];

                                $should[] = $musts;
                            }
                            $query['bool']['filter'][] = [
                                'bool' => ['should' => $should]
                            ];
                        } else {
                            $rangeQuery[] = [
                                'range' => [
                                    $filter['prop'] => [
                                        'gte' => Date::parse($filter['value'][0]),
                                        'lte' => Date::parse($filter['value'][1]),
                                        'format' => 'date_time',
                                    ],
                                ],
                            ];


                            $query['bool']['filter'][] = ['bool' => [
                                'should' => $rangeQuery
                            ]];
                        }


                        break;
                    case 'boolean':
                        $query['bool']['filter'][] = [
                            'bool' => [
                                'filter' => ['terms' => [$filter['prop'] => $filter['value']]],
                            ]
                        ];
                        break;
                    case 'custom':
                        if ($filter['prop'] == 'pay_date') {
                            $query['bool']['filter'][] = ['bool' => [
                                'must_not' => ['exists' => ['field' => $filter['prop']]]
                            ]];
                        }
                        break;
                }
            }
        }

        if ($config['sorts']) {
            foreach ($config['sorts'] as $sortable) {
                $key = array_search($sortable['prop'], array_column($config['all_columns'], 'prop'));
                if ($key !== false) {
                    $type = $config['all_columns'][$key]['type'];
                    if ($type === 'uuid') {
                        $sortable['prop'] = substr($sortable['prop'], -0, -3);
                        $type = 'string';
                    }
                    if ($type == 'string') {
                        $sort[] = [
                            $sortable['prop'] . '.keyword' => $sortable['dir'],
                        ];
                    } else {
                        $sort[] = [
                            $sortable['prop'] => $sortable['dir'],
                        ];
                    }
                }
            }
        } else {
            $sort[] = [
                'created_at' => 'desc'
            ];
        }
        $sort[] = [
            'id' => 'desc',
        ];

        foreach ($config['user_columns'] as $column) {
            if ($column['type'] == 'uuid' || $column['prop'] == 'company_id') {
                $new['prop'] = substr($column['prop'], -0, -3);
                array_push($config['user_columns'], $new);
            }
        }

        $preferredTables = array_map(function ($columns) {
            return $columns['prop'];
        }, $config['user_columns']);
        if (in_array($class, $entities)) {
            array_push($preferredTables, 'project_id');
            if (in_array('total_price', $preferredTables) && $inDollar) {
                array_push($preferredTables, 'total_price_usd');
            }
            if ($class == PurchaseOrder::class) {
                array_push($preferredTables, 'purchase_order_project', 'is_contractor');
            }
        }
        if ($class == Resource::class) {
            $needCurrency = true;
            if (!in_array('default_currency', $preferredTables)) {
                array_push($preferredTables, 'default_currency');
                $needCurrency = false;
            }
        }

        if ($class == Employee::class) {
            $needCurrency = true;
            if (!in_array('default_currency', $preferredTables)) {
                array_push($preferredTables, 'default_currency');
                $needCurrency = false;
            }
        }

        if ($class == Invoice::class) {
            array_push($preferredTables, 'download', 'legal_entity_id', 'total_paid_amount', 'total_paid_amount_usd');
            if (in_array('total_price', $preferredTables) && !in_array('currency_code', $preferredTables)) {
                array_push($preferredTables, 'currency_code');
            }
        }

        if ($class == ResourceInvoice::class) {
            if (in_array('total_price', $preferredTables) && !in_array('currency_code', $preferredTables)) {
                array_push($preferredTables, 'currency_code');
            }
        }

        if ($class == Quote::class) {
            if (in_array('total_price', $preferredTables) && !in_array('currency_code', $preferredTables)) {
                array_push($preferredTables, 'currency_code');
            }
            if (in_array('total_price', $preferredTables) && !in_array('total_price_usd', $preferredTables)) {
                array_push($preferredTables, 'total_price_usd');
            }
            if (in_array('total_price', $preferredTables) && !in_array('customer_currency_code', $preferredTables)) {
                array_push($preferredTables, 'customer_currency_code');
            }
            if (in_array('total_price', $preferredTables) && !in_array('customer_total_price', $preferredTables)) {
                array_push($preferredTables, 'customer_total_price');
            }
        }

        if ($class == LegalEntity::class) {
            if (!in_array('deleted_at', $preferredTables)) {
                array_push($preferredTables, 'deleted_at');
            }
        }

        if ($class == Order::class) {
            if (in_array('gross_margin', $preferredTables) && $inDollar) {
                array_push($preferredTables, 'gross_margin_usd');
            }
            if (in_array('costs', $preferredTables) && $inDollar) {
                array_push($preferredTables, 'costs_usd');
            }
            if (in_array('potential_gm', $preferredTables) && $inDollar) {
                array_push($preferredTables, 'potential_gm_usd');
            }
            if (in_array('potential_costs', $preferredTables) && $inDollar) {
                array_push($preferredTables, 'potential_costs_usd');
            }

            if (in_array('total_price', $preferredTables) && !in_array('currency_code', $preferredTables)) {
                array_push($preferredTables, 'currency_code');
            }

            if (in_array('gross_margin', $preferredTables) && !in_array('currency_code', $preferredTables)) {
                array_push($preferredTables, 'currency_code');
            }

            if (in_array('costs', $preferredTables) && !in_array('currency_code', $preferredTables)) {
                array_push($preferredTables, 'currency_code');
            }

            if (in_array('potential_gm', $preferredTables) && !in_array('currency_code', $preferredTables)) {
                array_push($preferredTables, 'currency_code');
            }

            if (in_array('potential_costs', $preferredTables) && !in_array('currency_code', $preferredTables)) {
                array_push($preferredTables, 'currency_code');
            }
        }

        if ($class == PurchaseOrder::class) {
            if (in_array('total_price', $preferredTables) && !in_array('currency_code', $preferredTables)) {
                array_push($preferredTables, 'currency_code');
            }
        }

        if ($class == InvoicePayment::class) {
            array_push($preferredTables, 'pay_amount', 'invoice_id', 'invoice', 'currency_code');
            if (in_array('pay_amount_usd', $preferredTables) && $inDollar) {
                array_push($preferredTables, 'pay_amount_usd');
            }
        }

        if ($class == EmployeeHistory::class && !in_array('default_currency', $preferredTables)) {
            array_push($preferredTables, 'default_currency');
        }


        $preferredTables = array_values(array_unique($preferredTables));

        $results = $class::searchByQuery($query, null, [], $preferredTables, $amount, $page * $amount, $sort)->toArray();

        if (in_array('hourly_rate', $preferredTables) || in_array('daily_rate', $preferredTables)) {
            $results = array_map(function ($item) use ($inDollar, $rates, $needCurrency, $preferredTables) {
                $resourceCurrency = CurrencyCode::make((int)$item['default_currency'])->__toString();
                if (array_key_exists('hourly_rate', $item)) {
                    $item['hourly_rate'] = $inDollar ? ceiling($item['hourly_rate'] * safeDivide(1, $rates['rates'][$resourceCurrency]) * $rates['rates']['USD'], 2) : ceiling($item['hourly_rate'] * safeDivide(1, $rates['rates'][$resourceCurrency]), 2);
                }
                if (array_key_exists('daily_rate', $item)) {
                    $item['daily_rate'] = $inDollar ? ceiling($item['daily_rate'] * safeDivide(1, $rates['rates'][$resourceCurrency]) * $rates['rates']['USD'], 2) : ceiling($item['daily_rate'] * safeDivide(1, $rates['rates'][$resourceCurrency]), 2);
                }
                if (!$needCurrency) {
                    unset($item['default_currency']);
                }
                return $item;
            }, $results);
        }

        if ($class == Service::class && in_array('price', $preferredTables) && ($resourceId === null)) {
            $rate = 1;
            if (UserRole::isAdmin(auth()->user()->role) && Company::find(getTenantWithConnection())->currency_code == CurrencyCode::USD()->getIndex()) {
                $rate = safeDivide(1, $rates['rates']['USD']);
            }
            $results = array_map(function ($item) use ($rate) {
                $item['price'] = ceiling($item['price'] * $rate, 2);
                return $item;
            }, $results);
        }
        if ($class == Employee::class && in_array('salary', $preferredTables)) {
            $results = array_map(function ($item) use ($inDollar, $rates, $needCurrency, $preferredTables) {
                $employeeCurrency = CurrencyCode::make((int)$item['default_currency'])->__toString();
                $item['salary'] = $inDollar ? ceiling($item['salary'] * safeDivide(1, $rates['rates'][$employeeCurrency]) *
                    $rates['rates']['USD'], 2) :
                    ceiling($item['salary'] * safeDivide(1, $rates['rates'][$employeeCurrency]), 2);

                if (!$needCurrency) {
                    unset($item['default_currency']);
                }

                return $item;
            }, $results);
        }

        if ($class == Order::class && (in_array('gross_margin', $preferredTables) || in_array('costs', $preferredTables)
            || in_array('potential_costs', $preferredTables) || in_array('potential_gm', $preferredTables))) {
            $results = array_map(function ($item) use ($inDollar) {
                if (array_key_exists('gross_margin', $item)  && $inDollar) {
                    $item['gross_margin'] = $item['gross_margin_usd'];
                    unset($item['gross_margin_usd']);
                }
                if (array_key_exists('costs', $item)  && $inDollar) {
                    $item['costs'] = $item['costs_usd'];
                    unset($item['costs_usd']);
                }
                if (array_key_exists('potential_gm', $item)  && $inDollar) {
                    $item['potential_gm'] = $item['potential_gm_usd'];
                    unset($item['potential_gm_usd']);
                }
                if (array_key_exists('potential_costs', $item)  && $inDollar) {
                    $item['potential_costs'] = $item['potential_costs_usd'];
                    unset($item['potential_costs']);
                }

                return $item;
            }, $results);
        }

        $class::resultConvertEpochToDateTime($results);

        $count = $class::countQuery($query);
        $rows = ['data' => $results, 'count' => $count];
        return $rows;
    }

    protected function getTableConfig($entity, $key)
    {
        $user = auth()->user();
        $table = strtolower(TablePreferenceType::make((int)$entity)->getName());
        $allColumns = $this->getAllColumns($table);
        $tablePreference = $user->table_preferences()->firstOrCreate(['type' => $entity, 'key' => $key], ['columns' => json_encode(config("table-config.$table.default")), 'filters' => json_encode(config("table-config.$table.default_filter")), 'sorts' => json_encode(config("table-config.$table.default_sorting"))]);

        $userColumns = json_decode($tablePreference->columns);
        $userColumns = $this->getColumnTypes($userColumns, $allColumns);
        return [
            'user_columns' => $userColumns,
            'all_columns' => $allColumns,
            'sorts' => json_decode($tablePreference->sorts, true),
            'filters' => json_decode($tablePreference->filters, true)
        ];
    }

    private function getAllColumns($table)
    {
        return config("table-config.$table.all");
    }

    private function getColumnTypes($columns, $allColumns)
    {
        $columns = array_map(function ($item) use ($allColumns) {
            $key = array_search($item, array_column($allColumns, 'prop'));
            $r = [];
            if ($key !== null || $key !== false) {
                $r['prop'] = $allColumns[$key]['prop'];
                $r['name'] = $allColumns[$key]['name'];
                $r['type'] = $allColumns[$key]['type'];
                if ($allColumns[$key]['type'] == 'enum') {
                    $r['enum'] = $allColumns[$key]['enum'];
                }
                if ($allColumns[$key]['type'] == 'uuid') {
                    $r['model'] = $allColumns[$key]['model'];
                }
            }
            return $r;
        }, $columns);
        return $columns;
    }

    private function singleResourceClass()
    {
        return $this->model::getResource();
    }
}
