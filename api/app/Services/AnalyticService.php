<?php


namespace App\Services;


use App\Contracts\Repositories\EarnOutStatusRepositoryInterface;
use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Enums\CommissionModel;
use App\Enums\CommissionPercentageType;
use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Mail\EarnOutApprovalNotification;
use App\Mail\EarnOutConfirmedNotification;
use App\Jobs\ElasticUpdateAssignment;
use App\Models\Commission;
use App\Models\Company;
use App\Models\CompanyLoan;
use App\Models\CompanyRent;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeHistory;
use App\Models\Invoice;
use App\Models\LoanPaymentLog;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Quote;
use App\Models\SalesCommission;
use App\Models\SalesCommissionPercentage;
use App\Repositories\AnalyticRepository;
use App\Repositories\Cache\CacheCommissionRepository;
use App\Repositories\Elastic\ElasticInvoiceRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\App;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;
use Tenancy\Facades\Tenancy;
use App\Services\CommissionService;

class AnalyticService
{
    /**
     * @var AnalyticRepository
     */
    protected AnalyticRepository $analytic_repository;
    /**
     * @var EarnOutStatusRepositoryInterface
     */
    protected EarnOutStatusRepositoryInterface $earnOutStatusRepository;
    /**
     * @var CommissionService
     */
    protected CommissionService $commissionService;
    /**
     * @var InvoicePaymentService
     */
    protected InvoicePaymentService $invoicePaymentService;
    /**
     * @var ElasticInvoiceRepository
     */
    private $elasticInvoiceRepository;
    /**
     * @var CacheCommissionRepository
     */
    private $cacheCommissionRepository;

    public function __construct(
        AnalyticRepository $analytic_repository,
        EarnOutStatusRepositoryInterface $earnOutStatusRepository,
        CommissionService $commissionService,
        InvoicePaymentService $invoicePaymentService,
        ElasticInvoiceRepository $elasticInvoiceRepository,
        CacheCommissionRepository $cacheCommissionRepository
    ) {
        $this->analytic_repository = $analytic_repository;
        $this->earnOutStatusRepository = $earnOutStatusRepository;
        $this->commissionService = $commissionService;
        $this->invoicePaymentService = $invoicePaymentService;
        $this->elasticInvoiceRepository = $elasticInvoiceRepository;
        $this->cacheCommissionRepository = $cacheCommissionRepository;
    }

    public function get($day, $week, $month, $quarter, $year)
    {
        return $this->analytic_repository->get($day, $week, $month, $quarter, $year);
    }

    public function getCompany($company_id, $day, $week, $month, $quarter, $year)
    {
        return $this->analytic_repository->getCompany($company_id, $day, $week, $month, $quarter, $year);
    }

    public function summary($entity, $day, $week, $month, $quarter, $year, $periods)
    {
        return $this->analytic_repository->summary($entity, $day, $week, $month, $quarter, $year, $periods);
    }

    public function summaryCompany($entity, $day, $week, $month, $quarter, $year, $periods, $company_id)
    {
        return $this->analytic_repository->summaryCompany($entity, $day, $week, $month, $quarter, $year, $periods, $company_id);
    }

    public function earnoutSummary(string $companyId, int $year, int $quarter): array
    {
        $periodEnded = false;
        $confirmed = false;
        $totalRevenue = 0;
        $totalCosts = 0;
        $totalExternalEmployeeCosts = 0;
        $totalLegacy = 0;
        $loanAmountLeft = 0;
        $totalLegacyBonus = 0;
        $orderArray = [];
        $legacyOrdersArray = [];
        $loansArray = [];
        $openLoansArray = [];
        $earnoutArray = [];
        $poArray = [];
        $totalAmountPoWithoutOrders = 0;

        if (UserRole::isOwner(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role)) {
            if ($quarter == 0) {
                $month = date('n');
                $quarter = ceil($month / 3);
            }
            $today = date('Y-m-d');

            if ($today < $year . '-04-20') {
                return $earnoutArray;
            } elseif ($today < $year . '-07-20' && $quarter > 1) {
                return $earnoutArray;
            } elseif ($today < $year . '-10-20' && $quarter > 2) {
                return $earnoutArray;
            } elseif ($today < $year + 1 . '-01-20' && $quarter > 3) {
                return $earnoutArray;
            }
        }


        $company = Company::findOrFail($companyId);
        $acquisitionDate = strtotime($company->acquisition_date);
        $earnoutYears = $company->earnout_years;
        $endOfEarnoutPeriod = strtotime('+' . $earnoutYears . ' years', $acquisitionDate);

        $timeArray = $this->analytic_repository->getStartAndEnd(0, 0, 0, $quarter, $year);

        if ($acquisitionDate > $timeArray['end'] || $endOfEarnoutPeriod < $timeArray['start']) {
            return $earnoutArray;
        }

        if ($acquisitionDate >= $timeArray['start'] && $acquisitionDate <= $timeArray['end'] && $quarter != 0) {
            return $earnoutArray;
        }

        if (
          $quarter == 0 && $acquisitionDate >= strtotime('-3 months', $timeArray['end'])
          && $acquisitionDate < $timeArray['end']
        ) {
            return $earnoutArray;
        }


        if ($quarter == 0 && $acquisitionDate >= $timeArray['start'] && $acquisitionDate < strtotime('-3 months', $timeArray['end'])) {
            $startKey = $acquisitionDate;
        } else {
            if (
            $acquisitionDate >= strtotime('-3 months', $timeArray['start'])
            && $acquisitionDate < $timeArray['start']
            ) {
                $startKey = $acquisitionDate;
            } else {
                $startKey = $timeArray['start'];
            }
        }

        if ($endOfEarnoutPeriod >= $timeArray['start'] && $endOfEarnoutPeriod <= $timeArray['end']) {
            $periodEnded = true;
            $endKey = strtotime('+3 months', $endOfEarnoutPeriod);
            $extendedSearch = $endKey;
        } else {
            $endKey = $timeArray['end'];
            $extendedSearch = strtotime('+40 days', $timeArray['end']);
        }

        $startOfQuarter = $quarter == 0 ? $endKey : getStartOfQuarter($year, $quarter);
        $approvalDate = $this->earnOutStatusRepository->firstByOrNull('quarter', $startOfQuarter);
        if ($approvalDate && $approvalDate->approved < '2021-12-31') {
            $extendedSearch = strtotime($approvalDate->approved);
            $confirmed = true;
        }

        $invoiceResult = $this->getOrdersForEarnout($startKey, $endKey, $extendedSearch, $confirmed);
        $customers = Arr::pluck($invoiceResult, 'key');
        $poResult = $this->getPurchaseOrdersForEarnout($startKey, $endKey);
        $customers2 = Arr::pluck($poResult, 'key');
        $orderResult = $this->getOrdersForPeriod($startKey, $endKey, $company);
        $poWithoutOrdersResult = $this->getPurchaseOrdersWithoutOrders($company, $startKey, $endKey);

        $orderResult = array_map(function ($result) use ($customers, $customers2) {
            $mustBeIncluded = false;
            foreach ($result['orders']['buckets'] as $bucket) {
                if ($bucket['salaries']['total_salary']['value'] > 0) {
                    if ($bucket['delivered_at']['buckets'][0]['doc_count'] > 0) {
                        $mustBeIncluded = true;
                    }
                }
            }

            if ($mustBeIncluded) {
                return $result;
            }
        }, $orderResult);

        $customers3 = Arr::pluck($orderResult, 'key');
        $customers = array_filter(array_unique(array_merge($customers, $customers2, $customers3)));
        $companyCustomers = Customer::whereIn('id', $customers)->get();
        $legacyCustomers = $company->legacyCustomers;
        $invoiceResult = collect($invoiceResult);
        $poResult = collect($poResult);
        $orderResult = collect($orderResult);

        foreach ($legacyCustomers as $legacyCustomer) {
            $resultArray = [];
            $customerLegacy = 0;
            $customerLegacyBonus = 0;
            $allDelivered = true;

            $legacyArray = [
            'name' => $legacyCustomer->name,
            'id' => $legacyCustomer->id
            ];

            $orders = $invoiceResult->where('key', $legacyCustomer->id)->first();

            if ($orders) {
                $orders = array_map(function ($orderId) use ($periodEnded, $endKey) {
                    if ($periodEnded) {
                        $order = Order::find($orderId['key']);
                        if ($order && !($order->delivered_at === null)) {
                            if (strtotime($order->delivered_at) < strtotime('-3 months', $endKey)) {
                                return $orderId['key'];
                            }
                        }
                    } else {
                        return $orderId['key'];
                    }
                }, $orders['orders']['buckets']);
                $orders = array_filter($orders);

                foreach ($orders as $order) {
                    $invoices = $this->getEarnoutsInPeriod($order, $company, $startKey, $endKey, $confirmed);

                    $itemArray = [
                        'name' => $invoices['hits']['hits'][0]['_source']['order'],
                        'id' => $invoices['hits']['hits'][0]['_source']['order_id'],
                        'project_id' => $invoices['hits']['hits'][0]['_source']['project_id'],
                        'legacy_revenue' => round($invoices['aggregations']['total_earnouts']['legacy_customer']['monetary_value']['value']
                        - $invoices['aggregations']['total_earnouts']['legacy_customer']['credit_notes_price']['value']
                        - $invoices['aggregations']['total_earnouts']['legacy_customer']['vat_value']['value']
                        - $invoices['aggregations']['total_earnouts']['legacy_customer']['credit_notes_vat']['value'], 2),
                        'delivered' => $invoices['hits']['hits'][0]['_source']['order_status'] != 1 && $invoices['hits']['hits'][0]['_source']['all_invoices_paid'],
                    ];
                    $legacyBonus = ($itemArray['legacy_revenue'] * $company->earnout_bonus) / 100;
                    $itemArray['legacy_bonus'] = round($legacyBonus, 2);
                    array_push($resultArray, $itemArray);
                    $totalLegacy += $itemArray['legacy_revenue'];
                    $customerLegacy += $itemArray['legacy_revenue'];
                    $customerLegacyBonus += $itemArray['legacy_bonus'];
                    $totalLegacyBonus += $itemArray['legacy_bonus'];
                    if ($itemArray['delivered'] == false) {
                        $allDelivered = false;
                    }
                }
            }
            if (!count($resultArray)) {
                $resultArray = [];
                $legacyArray['items'] = $resultArray;
                $legacyArray['all_delivered'] = $allDelivered;
            } else {
                $legacyArray['items'] = $resultArray;
                $legacyArray['total_legacy_revenue'] = $customerLegacy;
                $legacyArray['total_legacy_bonus'] = $customerLegacyBonus;
                $legacyArray['all_delivered'] = $allDelivered;
            }

            array_push($legacyOrdersArray, $legacyArray);
        }

        foreach ($companyCustomers as $customer) {
            $resultArray = [];
            $orderIds = [];
            $poOrderIds = [];
            $deliveredOrderIds = [];
            $customerRevenue = 0;
            $customerCosts = 0;
            $customerExternalEmployeeCosts = 0;
            $allDelivered = true;

            $customerArray = [
            'name' => $customer->name,
            'id' => $customer->id
            ];

            $deliveredOrders = $orderResult->where('key', $customer->id)->first();

            if ($deliveredOrders) {
                $deliveredOrderIds = array_map(function ($orderId) {
                    if ($orderId['delivered_at']['buckets'][0]['doc_count'] > 0) {
                        return $orderId['key'];
                    }
                }, $deliveredOrders['orders']['buckets']);
                $deliveredOrderIds = array_filter($deliveredOrderIds);
            }

            $orders = $invoiceResult->where('key', $customer->id)->first();
            if ($orders) {
                $orderIds = array_map(function ($orderId) use ($periodEnded, $endKey) {
                    if ($periodEnded) {
                        $order = Order::find($orderId['key']);
                        if ($order && !($order->delivered_at === null)) {
                            if (strtotime($order->delivered_at) < strtotime('-3 months', $endKey)) {
                                return $orderId['key'];
                            }
                        }
                    } else {
                        return $orderId['key'];
                    }
                }, $orders['orders']['buckets']);
            }
            $orderIds = array_filter($orderIds);

            $poOrders = $poResult->where('key', $customer->id)->first();
            if ($poOrders) {
                $poOrderIds = array_map(function ($orderId) {
                    return $orderId['key'];
                }, $poOrders['orders']['buckets']);
            }

            $orders = array_unique(array_merge($orderIds, $poOrderIds, $deliveredOrderIds));

            foreach ($orders as $order) {
                $isOrderFinished = null;
                $finishedOrder = null;
                $costs = 0;
                $externalEmployeeCost = 0;
                $hasInvoice = true;
                $setAsDelivered = false;

                if (in_array($order, $deliveredOrderIds)) {
                    $isOrderFinished = collect($deliveredOrders['orders']['buckets'])->where('key', $order)->first();
                    $setAsDelivered = true;
                } else {
                    $orderRecord = Order::find($order);
                    if ($orderRecord) {
                        $deliveredAt = strtotime($orderRecord->delivered_at);
                        $setAsDelivered = ($deliveredAt >= $startKey && $deliveredAt < $endKey) || $deliveredAt < $startKey;
                    }
                }

                $invoices = $this->getEarnoutsInPeriod($order, $company, $startKey, $endKey, $confirmed);
                $purchaseOrders = $this->getPurchaseOrdersForPeriod($order, $company, $startKey, $endKey);

                if (empty($invoices['hits']['hits'])) {
                    $hasInvoice = false;
                    if (!empty($purchaseOrders['hits']['hits']) && !empty($isOrderFinished)) {
                        $externalEmployeeCost = $isOrderFinished['salaries']['total_salary']['value'];
                    }
                } else {
                    if ($isOrderFinished && $invoices['hits']['hits'][0]['_source']['is_last_paid_invoice']) {
                        $externalEmployeeCost = $isOrderFinished['salaries']['total_salary']['value'];
                    }
                }

                if (!empty($purchaseOrders['hits']['hits'])) {
                    $costs = round($purchaseOrders['aggregations']['total_price']['value'] - $purchaseOrders['aggregations']['total_vat']['value'], 2);
                } else {
                    $finishedOrder = Order::find($order);
                }

                if (empty($invoices['hits']['hits']) && empty($purchaseOrders['hits']['hits']) && !empty($isOrderFinished['salaries'])) {
                    $externalEmployeeCost = $isOrderFinished['salaries']['total_salary']['value'];
                    if ($externalEmployeeCost > 0) {
                        $finishedOrder = Order::find($order);
                    } else {
                        continue;
                    }
                }

                if ($hasInvoice) {
                    $name = $invoices['hits']['hits'][0]['_source']['order'];
                    $id = $invoices['hits']['hits'][0]['_source']['order_id'];
                    $project = $invoices['hits']['hits'][0]['_source']['project_id'];
                } elseif ($finishedOrder) {
                    $name = $finishedOrder->number;
                    $id = $finishedOrder->id;
                    $project = $finishedOrder->project_id;
                } else {
                    $name = $purchaseOrders['hits']['hits'][0]['_source']['order'];
                    $id = $purchaseOrders['hits']['hits'][0]['_source']['order_id'];
                    $project = $purchaseOrders['hits']['hits'][0]['_source']['project_id'];
                }

                $itemArray = [
                'name' => $name,
                'id' => $id,
                'project_id' => $project,
                'revenue' => $hasInvoice ? round($invoices['aggregations']['total_earnouts']['monetary_value']['value']
                    - $invoices['aggregations']['total_earnouts']['credit_notes_price']['value']
                    - $invoices['aggregations']['total_earnouts']['vat_value']['value']
                    - $invoices['aggregations']['total_earnouts']['credit_notes_vat']['value'], 2) : 0,
                  'costs' => $costs,
                  'external_employee_costs' => $externalEmployeeCost,
                  'delivered' => $setAsDelivered,
                ];
                array_push($resultArray, $itemArray);
                $totalRevenue += $itemArray['revenue'];
                $totalCosts += $itemArray['costs'];
                $totalExternalEmployeeCosts += $itemArray['external_employee_costs'];
                $customerRevenue += $itemArray['revenue'];
                $customerCosts += $itemArray['costs'];
                $customerExternalEmployeeCosts += $itemArray['external_employee_costs'];
                if ($itemArray['delivered'] == false) {
                    $allDelivered = false;
                }
            }
            if (!count($resultArray)) {
                $resultArray = ['Order not delivered within earn out period.'];
            }
            $customerArray['items'] = $resultArray;
            $customerArray['total_costs'] = $customerCosts;
            $customerArray['total_external_employee_costs'] = $customerExternalEmployeeCosts;
            $customerArray['total_revenue'] = $customerRevenue;
            $customerArray['all_delivered'] = $allDelivered;
            array_push($orderArray, $customerArray);
        }

        if ($poWithoutOrdersResult['hits']['total'] > 0) {
            $isEuro = UserRole::isAdmin(auth()->user()->role) || $company->currency_code == CurrencyCode::EUR()->getIndex();
            $totalAmountPoWithoutOrders = $poWithoutOrdersResult['aggregations']['total_price']['value'] -
            $poWithoutOrdersResult['aggregations']['total_vat']['value'];
            $poCollection = collect($poWithoutOrdersResult['hits']['hits'])->groupBy('_source.resource_id');

            foreach ($poCollection as $resource) {
                $poWithoutOrdersArray = [];
                $totalAmount = 0;
                $resourceArray = [
                'resource_id' => $resource[0]['_source']['resource_id'],
                'resource' => $resource[0]['_source']['resource'],
                ];
                foreach ($resource as $hit) {
                    $amount = $isEuro ? $hit['_source']['total_price'] - $hit['_source']['total_vat'] :
                    $hit['_source']['total_price_usd'] - $hit['_source']['total_vat_usd'];
                    $poItemArray = [
                    'id' => $hit['_source']['id'],
                    'project_id' => $hit['_source']['project_id'],
                    'number' => $hit['_source']['number'],
                    'amount' => $amount,
                    'authorised_at' => $hit['_source']['authorised_date'] ? date('Y-m-d', $hit['_source']['authorised_date']) : '-',
                    'paid_at' => $hit['_source']['pay_date'] ? date('Y-m-d', $hit['_source']['pay_date']) : '-',
                    ];
                    array_push($poWithoutOrdersArray, $poItemArray);
                    $totalAmount += $amount;
                }
                $resourceArray['items'] = $poWithoutOrdersArray;
                $resourceArray['total_amount'] = round($totalAmount, 2);
                array_push($poArray, $resourceArray);
            }
        }

        $totalCosts = $totalCosts + $totalAmountPoWithoutOrders;

        $salaryArray = $this->getEmployeesForPeriod($startKey, $timeArray['end'], $endOfEarnoutPeriod, $periodEnded);
        $salaryCost = $salaryArray['total_internal_salary'];
        $rentArray = $this->getRentsForPeriod($startKey, $timeArray['end'], $endOfEarnoutPeriod, $periodEnded);
        $rentCosts = $rentArray['total_rent_costs'];
        $grossMargin = $totalRevenue - $totalCosts - $totalExternalEmployeeCosts - $salaryCost - $rentCosts;
        $legacyBonus = $totalLegacyBonus;
        $grossMarginBonus = ceiling(($grossMargin * $company->gm_bonus) / 100, 2);

        if ($grossMarginBonus < 0) {
            $grossMarginBonus = 0;
        }

        $companyLoans = CompanyLoan::with('paymentLogs')->whereDate('issued_at', '<', date('Y-m-d', $endKey))
          ->where(function ($query) use ($startKey) {
              $query->whereDate('paid_at', '>', date('Y-m-d', $startKey))
                  ->orWhere('paid_at', null);
          })->orderBy('issued_at')->get();

        if ($companyLoans->isNotEmpty()) {
            $extraLoanAmount = 0;
            $loanAmountPaid = 0;
            $dateOfStart = date('Y-m-d', $startKey);

            foreach ($companyLoans as $loan) {
                $paid = 0;
                if ($loan->issued_at < $dateOfStart) {
                    $logs = $loan->paymentLogs->where('pay_date', '<', date('Y-m-d', $endKey));
                    if ($logs->isNotEmpty()) {
                        $paid = UserRole::isAdmin(auth()->user()->role) ? $logs->sum('admin_amount') : $logs->sum('amount');
                    }
                    $loanAmountLeft += UserRole::isAdmin(auth()->user()->role) ? $loan->admin_amount - $paid :
                    $loan->amount - $paid;
                    $loanItemArray = [
                    'id' => $loan->id,
                    'description' => $loan->description,
                    'amount' => UserRole::isAdmin(auth()->user()->role) ? $loan->admin_amount : $loan->amount,
                    'already_paid' => $paid,
                    'issued_at' => $loan->issued_at,
                    'paid_at' => $loan->paid_at ?? '-',
                    ];
                    array_push($openLoansArray, $loanItemArray);
                } elseif ($loan->issued_at < date('Y-m-d', $endKey)) {
                    $logs = $loan->paymentLogs->where('pay_date', '<', date('Y-m-d', $endKey));
                    if ($logs->isNotEmpty()) {
                        $paid = UserRole::isAdmin(auth()->user()->role) ? $logs->sum('admin_amount') : $logs->sum('amount');
                    }
                    $extraLoanAmount += UserRole::isAdmin(auth()->user()->role) ? $loan->admin_amount - $paid :
                    $loan->amount - $paid;
                    $loanItemArray = [
                    'id' => $loan->id,
                    'description' => $loan->description,
                    'amount' => UserRole::isAdmin(auth()->user()->role) ? $loan->admin_amount : $loan->amount,
                    'already_paid' => $paid,
                    'issued_at' => $loan->issued_at,
                    'paid_at' => $loan->paid_at ?? '-',
                    ];
                    array_push($openLoansArray, $loanItemArray);
                }
            }

            if ($loanAmountLeft > 0) {
                $extraLoanAmount += $loanAmountLeft;
            } else {
                $loanAmountLeft = 0;
            }

            if ($extraLoanAmount > 0) {
                $loanAmountPaid = $extraLoanAmount - ($grossMarginBonus + $legacyBonus);
                if ($loanAmountPaid <= 0) {
                    $loanAmountPaid = $extraLoanAmount;
                } else {
                    $loanAmountPaid = $grossMarginBonus + $legacyBonus;
                }
            }

            $loansArray = [
            'loans' => $openLoansArray,
            'open_loan_amount_before_quarter' => $loanAmountLeft,
            'amount_of_loans_this_quarter' => $extraLoanAmount,
            'amount_paid_this_quarter' => $loanAmountPaid,
            'loan_amount_still_to_pay' => $extraLoanAmount - $loanAmountPaid,
            ];
        }

        $earnoutArray = [
          'orders_per_customer' => $orderArray,
          'orders_per_legacy_customer' => $legacyOrdersArray,
          'total_revenue' => round($totalRevenue, 2),
          'total_costs' => round($totalCosts, 2),
          'total_external_employee_costs' => round($totalExternalEmployeeCosts, 2),
          'salaries' => $salaryArray,
          'rents' => $rentArray,
          'gross_margin' => round($grossMargin, 2),
          'gross_margin_ratio' => $totalRevenue ? round(safeDivide($grossMargin, $totalRevenue) * 100, 2) : 0,
          'total_legacy_amount' => round($totalLegacy, 2),
          'gross_margin_percentage' => (float)$company->gm_bonus,
          'earnout_percentage' => (float)$company->earnout_bonus,
          'gross_margin_bonus' => $grossMarginBonus,
          'earnout_bonus' => $legacyBonus,
          'total_bonus' => $grossMarginBonus + $legacyBonus,
          'total_bonus_paid' => $companyLoans->isNotEmpty() ? $grossMarginBonus + $legacyBonus - $loansArray['amount_paid_this_quarter'] : $grossMarginBonus + $legacyBonus,
          'loans' => $loansArray,
          'purchase_orders_without_orders' => $poArray,
        ];

        return $earnoutArray;
    }

    private function getOrdersForEarnout(int $startKey, int $endKey, int $dateOfApproval, bool $confirmed)
    {
        if ($confirmed) {
            $extendedDate = strtotime('+20 days', $startKey);
        } else {
            $extendedDate = strtotime('+40 days', $startKey);
        }

        $query = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => [
                      [
                          'bool' => [
                              'must' => [
                                  'range' => [
                                      'close_date' => [
                                          'gte' => $startKey,
                                          'lte' => $dateOfApproval
                                      ]
                                  ],
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  ['match' => ['type' => InvoiceType::accrec()->getIndex()]]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  ['match' => ['status' => InvoiceStatus::paid()->getIndex()]]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  ['terms' => ['order_status' => [
                                      OrderStatus::delivered()->getIndex(),
                                      OrderStatus::invoiced()->getIndex(), OrderStatus::active()->getIndex()
                                  ]]]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'should' => [
                                  [
                                      'bool' => [
                                          'must' => [
                                              [
                                                  'range' => [
                                                      'pay_date' => [
                                                          'gte' => $startKey,
                                                          'lte' => $extendedDate
                                                      ]
                                                  ],
                                              ],
                                              [
                                                  'range' => [
                                                      'date' => [
                                                          'gte' => $startKey,
                                                          'lte' => null
                                                      ]
                                                  ]
                                              ]
                                          ]
                                      ],
                                  ],
                                  [
                                      'bool' => [
                                          'must' => [
                                              [
                                                  'range' => [
                                                      'pay_date' => [
                                                          'gt' => $extendedDate,
                                                          'lte' => null
                                                      ]
                                                  ],
                                              ],
                                              [
                                                  'range' => [
                                                      'date' => [
                                                          'gte' => null,
                                                          'lte' => $endKey
                                                      ]
                                                  ]
                                              ]
                                          ]
                                      ],
                                  ]
                              ],
                          ],
                      ]
                  ],
              ]
          ],
          'aggs' => [
              'by_customer' => [
                  'terms' => [
                      'field' => 'customer_id',
                      'size' => 10000,
                      'order' => ['_key' => 'asc']
                  ],
                  'aggs' => [
                      'orders' => [
                          'terms' => [
                              'field' => 'order_id',
                              'size' => 10000
                          ],
                      ],
                  ],
              ],
          ],
        ];

        $invoices = Invoice::searchBySingleQuery($query);

        return $invoices['aggregations']['by_customer']['buckets'];
    }

    private function getEarnoutsInPeriod(string $orderId, Company $company, $startDate, $endDate, $confirmed)
    {
        if (UserRole::isAdmin(auth()->user()->role) || $company->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_price = 'total_price';
            $total_vat = 'total_vat';
            $total_salary = 'project_info.external_employee_cost';
            $credit_price = 'credit_notes_total_price';
            $credit_vat = 'credit_notes_total_vat';
            $total_po_cost = 'project_info.po_cost';
            $total_po_vat = 'project_info.po_vat';
            $shadow_price = 'shadow_price';
            $shadow_vat = 'shadow_vat';
        } else {
            $total_price = 'total_price_usd';
            $total_vat = 'total_vat_usd';
            $total_salary = 'project_info.external_employee_cost_usd';
            $credit_price = 'credit_notes_total_price_usd';
            $credit_vat = 'credit_notes_total_vat_usd';
            $total_po_cost = 'project_info.po_cost_usd';
            $total_po_vat = 'project_info.po_vat_usd';
            $shadow_price = 'shadow_price_usd';
            $shadow_vat = 'shadow_vat_usd';
        }
        if ($confirmed) {
            $extendedDate = strtotime('+20 days', $startDate);
        } else {
            $extendedDate = strtotime('+40 days', $startDate);
        }

        $query = [
          'sort' => [
              'pay_date' => 'desc'
          ],
          'query' => [
              'bool' => [
                  'must' => [
                      [
                          'bool' => [
                              'must' => [
                                  'match' => ['order_id' => $orderId]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  'match' => ['type' => InvoiceType::accrec()->getIndex()]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'should' => [
                                  [
                                      'bool' => [
                                          'must' => [
                                              [
                                                  'range' => [
                                                      'pay_date' => [
                                                          'gte' => $startDate,
                                                          'lte' => $extendedDate
                                                      ]
                                                  ],
                                              ],
                                              [
                                                  'range' => [
                                                      'date' => [
                                                          'gte' => $startDate,
                                                          'lte' => null
                                                      ]
                                                  ]
                                              ]
                                          ]
                                      ],
                                  ],
                                  [
                                      'bool' => [
                                          'must' => [
                                              [
                                                  'range' => [
                                                      'pay_date' => [
                                                          'gt' => $extendedDate,
                                                          'lte' => null
                                                      ]
                                                  ],
                                              ],
                                              [
                                                  'range' => [
                                                      'date' => [
                                                          'gte' => null,
                                                          'lte' => $endDate
                                                      ]
                                                  ]
                                              ]
                                          ]
                                      ],
                                  ]
                              ],
                          ],
                      ]
                  ]
              ]
          ],
          'aggs' => [
              'total_earnouts' => [
                  'filter' => ['terms' => ['status' => [InvoiceStatus::paid()->getIndex()]]],
                  'aggs' => [
                      'monetary_value' => [
                          'sum' => [
                              'script' => [
                                  'lang' => 'painless',
                                  'inline' => "doc['$total_price'].value - doc['$shadow_price'].value"
                              ]
                          ]
                      ],
                      'vat_value' => [
                          'sum' => [
                              'script' => [
                                  'lang' => 'painless',
                                  'inline' => "doc['$total_vat'].value - doc['$shadow_vat'].value"
                              ]
                          ]
                      ],
                      'credit_notes_price' => [
                          'sum' => [
                              'field' => $credit_price
                          ]
                      ],
                      'credit_notes_vat' => [
                          'sum' => [
                              'field' => $credit_vat
                          ]
                      ],
                      'legacy_customer' => [
                          'filter' => [
                              'term' => ['legacy_customer' => true]
                          ],
                          'aggs' => [
                              'monetary_value' => [
                                  'sum' => [
                                      'script' => [
                                          'lang' => 'painless',
                                          'inline' => "doc['$total_price'].value - doc['$shadow_price'].value"
                                      ]
                                  ]
                              ],
                              'vat_value' => [
                                  'sum' => [
                                      'script' => [
                                          'lang' => 'painless',
                                          'inline' => "doc['$total_vat'].value - doc['$shadow_vat'].value"
                                      ]
                                  ]
                              ],
                              'credit_notes_price' => [
                                  'sum' => [
                                      'field' => $credit_price
                                  ]
                              ],
                              'credit_notes_vat' => [
                                  'sum' => [
                                      'field' => $credit_vat
                                  ]
                              ],
                          ]
                      ],
                      'external_salary' => [
                          'nested' => ['path' => 'project_info'],
                          'aggs' => [
                              'monetary_value' => [
                                  'avg' => [
                                      'field' => $total_salary
                                  ]
                              ],
                          ],
                      ],
                      'total_costs' => [
                          'nested' => ['path' => 'project_info'],
                          'aggs' => [
                              'monetary_value' => [
                                  'avg' => [
                                      'field' => $total_po_cost
                                  ]
                              ],
                              'vat_value' => [
                                  'avg' => [
                                      'field' => $total_po_vat
                                  ]
                              ],
                          ],
                      ],
                  ],
              ],
          ],
        ];

        return Invoice::searchBySingleQuery($query);
    }

    private function getRentsForPeriod($startDate, $endDate, $endOfEarnOut, $periodEnded = false)
    {
        $rentArray = [];
        $itemsArray = [];
        $totalRentCosts = 0;

        if ($periodEnded) {
            $searchUntilDate = $endOfEarnOut;
        } else {
            $searchUntilDate = $endDate;
        }

        $query = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must_not' => [
                      [
                          'range' => [
                              'end_date' => [
                                  'lte' => $startDate
                              ]
                          ]
                      ],
                      [
                          'range' => [
                              'start_date' => [
                                  'gte' => $searchUntilDate
                              ]
                          ]
                      ],
                      [
                          'exists' => [
                              'field' => 'deleted_at'
                          ]
                      ]
                  ]
              ]
          ],
          'aggs' => [
              'id' => [
                  'terms' => [
                      'field' => 'id',
                      'size' => 10000,
                  ],
              ],
          ],
        ];

        $rents = CompanyRent::searchBySingleQuery($query);

        $rentIds = array_map(function ($rentId) {
            return $rentId['key'];
        }, $rents['aggregations']['id']['buckets']);

        foreach ($rentIds as $rentId) {
            $item = $this->getRentCosts($startDate, $endDate, $rentId, $endOfEarnOut);
            $totalRentCosts += $item['cost_for_this_quarter'];
            array_push($itemsArray, $item);
        }
        $rentArray['items'] = $itemsArray;
        $rentArray['total_rent_costs'] = $totalRentCosts;

        return $rentArray;
    }

    private function getRentCosts($startDate, $endDate, $rentId, $endOfEarnOut)
    {
        if (UserRole::isAdmin(auth()->user()->role)) {
            $amount = 'admin_amount';
        } else {
            $amount = 'amount';
        }

        $query = [
          'query' => [
              'bool' => [
                  'must' => [
                      'match' => ['id' => $rentId]
                  ],
                  'must_not' => [
                      [
                          'range' => [
                              'end_date' => [
                                  'lte' => $startDate
                              ]
                          ]
                      ],
                      [
                          'range' => [
                              'start_date' => [
                                  'gte' => $endDate
                              ]
                          ]
                      ],
                  ]
              ]
          ],
          'aggs' => [
              'costs' => [
                  'sum' => [
                      'field' => $amount
                  ],
              ],
              'started_in_period' => [
                  'date_range' => [
                      'field' => 'start_date',
                      'ranges' => [
                          ['from' => $startDate, 'to' => $endDate],
                      ]
                  ],
                  'aggs' => [
                      'started_on' => [
                          'terms' => [
                              'field' => 'start_date',
                          ],
                          'aggs' => [
                              'costs' => [
                                  'sum' => [
                                      'field' => $amount
                                  ]
                              ],
                          ]
                      ],
                  ]
              ],
              'ended_in_period' => [
                  'date_range' => [
                      'field' => 'end_date',
                      'ranges' => [
                          ['from' => $startDate, 'to' => $endDate],
                      ]
                  ],
                  'aggs' => [
                      'ended_on' => [
                          'terms' => [
                              'field' => 'end_date',
                          ],
                          'aggs' => [
                              'costs' => [
                                  'sum' => [
                                      'field' => $amount
                                  ]
                              ],
                          ]
                      ],
                  ]
              ],
          ]
        ];

        $rents = CompanyRent::searchBySingleQuery($query);

        $totalMonths = ((date('Y', $endDate) - date('Y', $startDate)) * 12) + (date('m', $endDate) - date('m', $startDate));

        $totalRent = $rents['aggregations']['costs']['value'] * $totalMonths;
        $startedIn = $rents['aggregations']['started_in_period']['buckets'];
        $endedIn = $rents['aggregations']['ended_in_period']['buckets'];
        $notFullRentStart = [];
        $notFullRentEnd = [];
        $rentAfterEarnOut = 0;

        if ($startedIn[0]['doc_count'] > 0) {
            $startOfMonth = strtotime(date('Y-m-01', $startedIn[0]['from_as_string']));

            $notFullRentStart = array_map(function ($k) use ($startOfMonth, $rentId) {
                $rentNotToPay = 0;
                $isEndOfPeriod = $k['key_as_string'];
                $startOfPeriod = strtotime(date('Y-m-01', $isEndOfPeriod));

                while ($isEndOfPeriod != $startOfMonth) {
                    $month = date('m', $startOfPeriod);
                    $year = date('Y', $startOfPeriod);
                    $daysInMonth = $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
                    $daysNotIncluded = ceil(abs($isEndOfPeriod - $startOfPeriod) / 86400);
                    $dayRent = safeDivide($k['costs']['value'], $daysInMonth);
                    $rentNotToPay += $daysNotIncluded * $dayRent;
                    $isEndOfPeriod = $startOfPeriod;
                    $startOfPeriod = strtotime('-1 month', $startOfPeriod);
                }

                return $rentNotToPay;
            }, $startedIn[0]['started_on']['buckets']);
        }
        $sumStart = array_sum($notFullRentStart);

        if ($endedIn[0]['doc_count'] > 0) {
            $notFullRentEnd = array_map(function ($k) use ($endedIn) {
                $rentNotToPay = 0;
                $isEndOfPeriod = $k['key_as_string'];
                $nextMonth = date('m', $isEndOfPeriod) + 1;
                if ($nextMonth > 12) {
                    $nextMonth = $nextMonth % 12;
                }
                $startOfPeriod = strtotime(date('Y-' . $nextMonth . '-01', strtotime('+1 month', $isEndOfPeriod)));

                while ($isEndOfPeriod != $endedIn[0]['to_as_string']) {
                    $year = date('Y', $isEndOfPeriod);
                    $month = date('m', $isEndOfPeriod);
                    $daysInMonth = $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
                    $daysNotIncluded = ceil(abs($startOfPeriod - $isEndOfPeriod) / 86400);
                    $dayRent = safeDivide($k['costs']['value'], $daysInMonth);
                    $rentNotToPay += $daysNotIncluded * $dayRent;
                    $isEndOfPeriod = $startOfPeriod;
                    $startOfPeriod = strtotime('+1 month', $startOfPeriod);
                }

                return $rentNotToPay;
            }, $endedIn[0]['ended_on']['buckets']);
        }
        $sumEnd = array_sum($notFullRentEnd);

        if ($endOfEarnOut > $startDate && $endOfEarnOut < $endDate) {
            $endOfRent = $rents['hits']['hits'][0]['_source']['end_date'];
            $salary = $rents['aggregations']['costs']['value'];
            if (($endOfRent === null) || $endOfRent > $endDate) {
                $daysAfterEarnOut = ceil(abs($endOfEarnOut - $endDate) / 86400);
                $dayRent = $salary / 30;
                $rentAfterEarnOut = $daysAfterEarnOut * $dayRent;
            } elseif ($endOfRent > $endOfEarnOut && $endOfRent < $endDate) {
                $daysAfterEarnOut = ceil(abs($endOfEarnOut - $endOfRent) / 86400);
                $dayRent = $salary / 30;
                $rentAfterEarnOut = $daysAfterEarnOut * $dayRent;
            }
        }

        $itemsArray = [
          'id' => $rents['hits']['hits'][0]['_source']['id'],
          'name' => $rents['hits']['hits'][0]['_source']['name'],
          'start_of_rent' => date('Y-m-d', $rents['hits']['hits'][0]['_source']['start_date']),
          'end_of_rent' => $rents['hits']['hits'][0]['_source']['end_date'] ?
              date('Y-m-d', $rents['hits']['hits'][0]['_source']['end_date']) : '-',
          'cost_per_month' => $rents['hits']['hits'][0]['_source'][$amount],
          'cost_for_this_quarter' => round($totalRent - $sumStart - $sumEnd - $rentAfterEarnOut, 2),
        ];

        return $itemsArray;
    }

    private function getEmployeesForPeriod($startDate, $endDate, $endOfEarnOut, $periodEnded = false)
    {
        $totalEmployeeCosts = 0;
        $employeeArray = [];
        $itemsArray = [];

        if ($periodEnded) {
            $searchUntilDate = $endOfEarnOut;
        } else {
            $searchUntilDate = $endDate;
        }

        $query = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must_not' => [
                      [
                          'range' => [
                              'end_date' => [
                                  'lte' => $startDate
                              ]
                          ]
                      ],
                      [
                          'range' => [
                              'start_date' => [
                                  'gte' => $searchUntilDate
                              ]
                          ]
                      ]

                  ]
              ]
          ],
          'aggs' => [
              'id' => [
                  'terms' => [
                      'field' => 'id',
                      'size' => 10000,
                  ],
              ],
          ],
        ];

        $employees = EmployeeHistory::searchBySingleQuery($query);

        $employeeIds = array_map(function ($employeeId) {
            return $employeeId['key'];
        }, $employees['aggregations']['id']['buckets']);

        foreach ($employeeIds as $employeeId) {
            $item = $this->getEmployeeCosts($startDate, $endDate, $employeeId, $endOfEarnOut);
            $totalEmployeeCosts += $item['salary_for_this_quarter'];
            array_push($itemsArray, $item);
        }
        $employeeArray['items'] = $itemsArray;
        $employeeArray['total_internal_salary'] = $totalEmployeeCosts;

        return $employeeArray;
    }

    private function getEmployeeCosts($startDate, $endDate, $employeeId, $endOfEarnOut)
    {
        if (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex()) {
            $salary = 'salary';
        } else {
            $salary = 'salary_usd';
        }

        $query = [
          'query' => [
              'bool' => [
                  'must' => [
                      'match' => ['id' => $employeeId]
                  ],
                  'must_not' => [
                      [
                          'range' => [
                              'end_date' => [
                                  'lte' => $startDate
                              ]
                          ]
                      ],
                      [
                          'range' => [
                              'start_date' => [
                                  'gte' => $endDate
                              ]
                          ]
                      ]

                  ]
              ]
          ],
          'aggs' => [
              'salary' => [
                  'sum' => [
                      'field' => $salary
                  ]
              ],
              'started_in_period' => [
                  'date_range' => [
                      'field' => 'start_date',
                      'ranges' => [
                          ['from' => $startDate, 'to' => $endDate],
                      ]
                  ],
                  'aggs' => [
                      'started_on' => [
                          'terms' => [
                              'field' => 'start_date',
                          ],
                          'aggs' => [
                              'salary' => [
                                  'sum' => [
                                      'field' => $salary
                                  ]
                              ],
                          ]
                      ],
                  ]
              ],
              'ended_in_period' => [
                  'date_range' => [
                      'field' => 'end_date',
                      'ranges' => [
                          ['from' => $startDate, 'to' => $endDate],
                      ]
                  ],
                  'aggs' => [
                      'ended_on' => [
                          'terms' => [
                              'field' => 'end_date',
                          ],
                          'aggs' => [
                              'salary' => [
                                  'sum' => [
                                      'field' => $salary
                                  ]
                              ],
                          ]
                      ],
                  ]
              ],
          ]
        ];

        $salaries = EmployeeHistory::searchBySingleQuery($query);

        $totalMonths = ((date('Y', $endDate) - date('Y', $startDate)) * 12) + (date('m', $endDate) - date('m', $startDate));
        $totalSalary = $salaries['aggregations']['salary']['value'] * $totalMonths;

        $startedIn = $salaries['aggregations']['started_in_period']['buckets'];
        $endedIn = $salaries['aggregations']['ended_in_period']['buckets'];
        $notFullSalaryStart = [];
        $notFullSalaryEnd = [];
        $salaryAfterEarnOut = 0;

        if ($startedIn[0]['doc_count'] > 0) {
            $startOfMonth = strtotime(date('Y-m-01', $startedIn[0]['from_as_string']));
            $notFullSalaryStart = array_map(function ($k) use ($startOfMonth) {
                $salaryNotToPay = 0;
                $isEndOfPeriod = $k['key_as_string'];
                $startOfPeriod = strtotime(date('Y-m-01', $isEndOfPeriod));

                while ($isEndOfPeriod != $startOfMonth) {
                    $month = date('m', $startOfPeriod);
                    $year = date('Y', $startOfPeriod);
                    $daysInMonth = $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
                    $daysNotWorked = ceil(abs($isEndOfPeriod - $startOfPeriod) / 86400);
                    $daySalary = safeDivide($k['salary']['value'], $daysInMonth);
                    $salaryNotToPay += $daysNotWorked * $daySalary;
                    $isEndOfPeriod = $startOfPeriod;
                    $startOfPeriod = strtotime('-1 month', $startOfPeriod);
                }

                return $salaryNotToPay;
            }, $startedIn[0]['started_on']['buckets']);
        }
        $sumStart = array_sum($notFullSalaryStart);

        if ($endedIn[0]['doc_count'] > 0) {
            $notFullSalaryEnd = array_map(function ($k) use ($endedIn) {
                $salaryNotToPay = 0;
                $isEndOfPeriod = $k['key_as_string'];
                $nextMonth = date('m', $isEndOfPeriod) + 1;
                if ($nextMonth > 12) {
                    $nextMonth = $nextMonth % 12;
                }
                $startOfPeriod = strtotime(date('Y-' . $nextMonth . '-01', strtotime('+1 month', $isEndOfPeriod)));

                while ($isEndOfPeriod != $endedIn[0]['to_as_string']) {
                    $year = date('Y', $isEndOfPeriod);
                    $month = date('m', $isEndOfPeriod);
                    $daysInMonth = $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
                    $daysNotWorked = ceil(abs($startOfPeriod - $isEndOfPeriod) / 86400);
                    $daySalary = safeDivide($k['salary']['value'], $daysInMonth);
                    $salaryNotToPay += $daysNotWorked * $daySalary;
                    $isEndOfPeriod = $startOfPeriod;
                    $startOfPeriod = strtotime('+1 month', $startOfPeriod);
                }

                return $salaryNotToPay;
            }, $endedIn[0]['ended_on']['buckets']);
        }
        $sumEnd = array_sum($notFullSalaryEnd);

        if ($endOfEarnOut > $startDate && $endOfEarnOut < $endDate) {
            $endOfEmployment = $salaries['hits']['hits'][0]['_source']['end_date'];
            $employeeSalary = $salaries['aggregations']['salary']['value'];
            if (($endOfEmployment === null) || $endOfEmployment > $endDate) {
                $daysAfterEarnOut = ceil(abs($endOfEarnOut - $endDate) / 86400);
                $daySalary = $employeeSalary / 30;
                $salaryAfterEarnOut = $daysAfterEarnOut * $daySalary;
            } elseif ($endOfEmployment > $endOfEarnOut && $endOfEmployment < $endDate) {
                $daysAfterEarnOut = ceil(abs($endOfEarnOut - $endOfEmployment) / 86400);
                $daySalary = $employeeSalary / 30;
                $salaryAfterEarnOut = $daysAfterEarnOut * $daySalary;
            }
        }

        $itemsArray = [
          'id' => $salaries['hits']['hits'][0]['_source']['employee_id'],
          'name' => $this->getEmployeeName($salaries['hits']['hits'][0]['_source']['employee_id']),
          'start_of_employment' => date('Y-m-d', $salaries['hits']['hits'][0]['_source']['start_date']),
          'end_of_employment' => $salaries['hits']['hits'][0]['_source']['end_date'] ?
              date('Y-m-d', $salaries['hits']['hits'][0]['_source']['end_date']) : '-',
          'salary_per_month' => $salaries['hits']['hits'][0]['_source'][$salary],
          'salary_for_this_quarter' => round($totalSalary - $sumStart - $sumEnd - $salaryAfterEarnOut, 2),
        ];

        return $itemsArray;
    }

    private function getEmployeeName($employeeId)
    {
        $query = [
          'query' => [
              'bool' => [
                  'must' => [
                      'match' => ['id' => $employeeId]
                  ],
              ],
          ],
        ];
        $employee = Employee::searchBySingleQuery($query);

        if (!empty($employee['hits']['hits'])) {
            return $employee['hits']['hits'][0]['_source']['name'];
        }

        return null;
    }

    public function setAsApproved(string $companyId, string $owner, int $year, int $quarter): string
    {
        $startQuarter = getStartOfQuarter($year, $quarter);
        $mailAddresses = [];
        $company = Company::findOrFail($companyId);

        $message = $this->checkIfEarnOutsExists($company, $year, $quarter);
        if ($message != 'ok') {
            throw new UnprocessableEntityHttpException($message);
        }

        $alreadyExists = $this->earnOutStatusRepository->firstByOrNull('quarter', $startQuarter);

        if ($alreadyExists) {
            throw new UnprocessableEntityHttpException('Approval already sent.');
        }
        $this->earnOutStatusRepository->create([
          'quarter' => $startQuarter,
          'approved' => now(),
        ]);

        /*pay of loan with earned bonuses*/
        $loans = CompanyLoan::where('paid_at', null)->orderBy('issued_at')->get();

        if ($loans->isNotEmpty()) {
            $earnOutBonus = $this->earnoutSummary($companyId, $year, $quarter)['total_bonus'];
            $rate = getCurrencyRates()['rates']['USD'];

            foreach ($loans as $loan) {
                if ($earnOutBonus >= $loan->amount_left) {
                    $earnOutBonus -= $loan->amount_left;
                    $loan->update(['paid_at' => now(), 'amount_left' => 0, 'admin_amount_left' => 0]);
                    LoanPaymentLog::create([
                    'loan_id'       => $loan->id,
                    'amount'        => $loan->amount_left,
                    'admin_amount'  => $loan->admin_amount_left,
                    'pay_date'      => now(),
                    ]);
                } else {
                    $amountLeft = $loan->amount_left - $earnOutBonus;
                    if ($company->currency_code == CurrencyCode::EUR()->getIndex()) {
                        $adminLeft = $amountLeft;
                        $adminBonus = $earnOutBonus;
                    } else {
                        $adminLeft = $amountLeft * safeDivide(1, $rate);
                        $adminBonus = $earnOutBonus * safeDivide(1, $rate);
                    }

                    $loan->update(['amount_left' => $amountLeft, 'admin_amount_left' => $adminLeft]);
                    LoanPaymentLog::create([
                    'loan_id'       => $loan->id,
                    'amount'        => $earnOutBonus,
                    'admin_amount'  => $adminBonus,
                    'pay_date'      => now(),
                    ]);
                    break;
                }
            }
        }

        $accountants = User::where([['company_id', $companyId], ['role', UserRole::accountant()->getIndex()]])->get();
        foreach ($accountants as $accountant) {
            array_push($mailAddresses, $accountant->email);
        }

        if (count($mailAddresses)) {
            $thisQuarter = 'Q' . $quarter . ' ' . $year;

            try {
                Mail::to($mailAddresses)
                ->queue(new EarnOutApprovalNotification($owner, $thisQuarter, $company->name));
                $message = 'Approval sent to accountancy.';
            } catch (\Exception $exception) {
                $message = 'Approval set but failed to sent mail to accountancy.';
            }

            return $message;
        }

        return 'Approval set but no accountant found to send a mail.';
    }

    public function setAsConfirmed(string $companyId, string $loggedUser, int $year, int $quarter): string
    {
        $startQuarter = getStartOfQuarter($year, $quarter);
        $mailAddresses = [];
        $earnOutStatus = $this->earnOutStatusRepository->firstByOrNull('quarter', $startQuarter);

        if (!$earnOutStatus) {
            throw new UnprocessableEntityHttpException('Earn out not yet approved by company owner.');
        }

        if ($earnOutStatus->confirmed) {
            throw new UnprocessableEntityHttpException('Payment already confirmed.');
        }

        $this->earnOutStatusRepository->update($earnOutStatus->id, ['confirmed' => now()]);
        $owners = User::where([['company_id', $companyId], ['role', UserRole::owner()->getIndex()]])->get();
        foreach ($owners as $owner) {
            array_push($mailAddresses, $owner->email);
        }

        if (count($mailAddresses)) {
            $thisQuarter = 'Q' . $quarter . ' ' . $year;

            try {
                Mail::to($mailAddresses)
                ->queue(new EarnOutConfirmedNotification($loggedUser, $thisQuarter));
                $message = 'Confirmation sent to company owners.';
            } catch (\Exception $exception) {
                $message = 'Confirmation set but failed to sent mail to company owners.';
            }

            return $message;
        }

        return 'Confirmation set but no owner found to send a mail.';
    }

    public function setAsReceived(int $year, int $quarter): string
    {
        $startQuarter = getStartOfQuarter($year, $quarter);
        $earnOutStatus = $this->earnOutStatusRepository->firstByOrNull('quarter', $startQuarter);

        if (!$earnOutStatus || !$earnOutStatus->confirmed) {
            throw new UnprocessableEntityHttpException('Payment not yet confirmed.');
        }

        if ($earnOutStatus->received) {
            throw new UnprocessableEntityHttpException('Payment already set as received.');
        }

        $this->earnOutStatusRepository->update($earnOutStatus->id, ['received' => now()]);

        return 'Payment of earn out set as received.';
    }

    public function getStatus(int $year, int $quarter)
    {
        $startQuarter = getStartOfQuarter($year, $quarter);

        return $this->earnOutStatusRepository->firstByOrNull('quarter', $startQuarter);
    }

    public function earnOutProspection(string $companyId, int $year, int $quarter): array
    {
        $monthArray['months'] = [];
        $noEarnOutsForQuarter = false;
        $startOfQuarter = getStartOfQuarter($year, $quarter);
        $firstMonth = date('F', $startOfQuarter) . ' ' . date('y', $startOfQuarter);
        $endOfFirstMonth = strtotime('+1 month', $startOfQuarter);
        $secondMonth = date('F', $endOfFirstMonth) . ' ' . date('y', $endOfFirstMonth);
        $endOfSecondMonth = strtotime('+2 month', $startOfQuarter);
        $thirdMonth = date('F', $endOfSecondMonth) . ' ' . date('y', $endOfSecondMonth);
        $endOfQuarter = strtotime('+3 month', $startOfQuarter);
        $company = Company::findOrFail($companyId);
        $acquisitionDate = strtotime($company->acquisition_date);
        $endOfEarnOutPeriod = strtotime('+' . $company->earnout_years . ' years', $acquisitionDate);

        if ($acquisitionDate > $startOfQuarter && $acquisitionDate < $endOfQuarter) {
            $noEarnOutsForQuarter = true;
        }

        if ($acquisitionDate >= strtotime('-3 months', $startOfQuarter) && $acquisitionDate < $startOfQuarter) {
            $startOfQuarter = $acquisitionDate;
        }

        if (time() < $endOfFirstMonth) {
            $firstMonthArray = $this->earnOutForStartedMonth($company, $startOfQuarter, $endOfFirstMonth, $endOfEarnOutPeriod);
            $secondMonthArray = $this->earnOutForNotStartedMonth($company, $endOfFirstMonth, $endOfSecondMonth, $endOfEarnOutPeriod, 1);
            $thirdMonthArray = $this->earnOutForNotStartedMonth($company, $endOfSecondMonth, $endOfQuarter, $endOfEarnOutPeriod, 2);
        } elseif (time() > $endOfFirstMonth && time() < $endOfSecondMonth) {
            $firstMonthArray = $this->earnOutForEndedMonth($company, $startOfQuarter, $endOfFirstMonth, $endOfEarnOutPeriod);
            $secondMonthArray = $this->earnOutForStartedMonth($company, $endOfFirstMonth, $endOfSecondMonth, $endOfEarnOutPeriod);
            $thirdMonthArray = $this->earnOutForNotStartedMonth($company, $endOfSecondMonth, $endOfQuarter, $endOfEarnOutPeriod, 2);
        } else {
            $firstMonthArray = $this->earnOutForEndedMonth($company, $startOfQuarter, $endOfFirstMonth, $endOfEarnOutPeriod);
            $secondMonthArray = $this->earnOutForEndedMonth($company, $endOfFirstMonth, $endOfSecondMonth, $endOfEarnOutPeriod);
            $thirdMonthArray = $this->earnOutForStartedMonth($company, $endOfSecondMonth, $endOfQuarter, $endOfEarnOutPeriod);
        }
        $firstMonthArray['name'] = $firstMonth;
        $secondMonthArray['name'] = $secondMonth;
        $thirdMonthArray['name'] = $thirdMonth;

        array_push($monthArray['months'], $firstMonthArray, $secondMonthArray, $thirdMonthArray);
        $monthArray['total_gm'] = $firstMonthArray['monthly_total'] + $secondMonthArray['monthly_total'] + $thirdMonthArray['monthly_total'];
        $monthArray['total_legacy'] = $firstMonthArray['total_legacy'] + $secondMonthArray['total_legacy'] + $thirdMonthArray['total_legacy'];
        $monthArray['total_gm_bonus'] = ceiling(($monthArray['total_gm'] * $company->gm_bonus) / 100, 2);
        $monthArray['total_legacy_bonus'] = ceiling(($monthArray['total_legacy'] * $company->earnout_bonus) / 100, 2);
        $monthArray['total_bonus'] = $monthArray['total_gm_bonus'] + $monthArray['total_legacy_bonus'];
        $monthArray['just_acquired'] = $noEarnOutsForQuarter;

        return $monthArray;
    }

    private function checkIfEarnOutsExists(Company $company, int $year, int $quarter): string
    {
        if (!$company->acquisition_date || $company->earnout_years <= 0) {
            return 'Your company does not get earn outs.';
        }

        $today = date('Y-m-d');

        if ($today < $year . '-04-20') {
            return 'You can only approve from the 20th of april ' . $year . '.';
        } elseif ($today < $year . '-07-20' && $quarter == 2) {
            return 'You can only approve from the 20th of july ' . $year . '.';
        } elseif ($today < $year . '-10-20' && $quarter == 3) {
            return 'You can only approve from the 20th of october ' . $year . '.';
        } elseif ($today < $year + 1 . '-01-20' && $quarter == 4) {
            return 'You can only approve from the 20th of january ' . ($year + 1) . '.';
        }

        $acquisitionDate = strtotime($company->acquisition_date);
        $endOfEarnOut = strtotime('+' . $company->earnout_years . ' years', $acquisitionDate);
        $startOfQuarter = getStartOfQuarter($year, $quarter);
        $endOfQuarter = strtotime('+3 months', $startOfQuarter);

        if ($endOfEarnOut < $startOfQuarter) {
            return 'The period your company gets earn outs has ended.';
        }

        if ($acquisitionDate >= $startOfQuarter && $acquisitionDate <= $endOfQuarter) {
            return 'Earn outs for the first quarter after the acquisition are calculated in the next quarter.';
        }

        if ($endOfEarnOut >= $startOfQuarter && $endOfEarnOut <= $endOfQuarter) {
            if (time() < strtotime('+20 days', $endOfQuarter)) {
                return 'Last quarter for earn outs can be approved after the 20th of next quarter.';
            }
        }

        if ($acquisitionDate > $startOfQuarter) {
            return 'Your company was not yet acquired.';
        }

        return 'ok';
    }


    /**
     * commissionSummary
     *
     * @param  mixed $salespersonId
     * @param  mixed $day
     * @param  mixed $week
     * @param  mixed $month
     * @param  mixed $quarter
     * @param  mixed $year
     * @param  mixed $totalCommission
     * @return array
     */
    public function commissionSummary($salespersonId, $day, $week, $month, $quarter, $year, $totalCommission = false): array
    {
        $resultArr = [];
        $filterBySalesPersonIds = [];
        $totalAllCompaniesCommission = 0;
        $baseCommission = $this->getBaseCommission($salespersonId);
        $countTotalCommissions = 0;

        if ($salespersonId) {
            $filterBySalesPersonIds = getAllSalespersonIds($salespersonId);
        }

        if ($totalCommission && $salespersonId) {
            $timeArray['start'] = strtotime('2015-01-01');
            $timeArray['end'] = time();
        } else {
            $timeArray = $this->analytic_repository->getStartAndEnd($day, $week, $month, $quarter, $year);
        }

        if (UserRole::isOwner(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role)) {
            $companies = Company::where('id', auth()->user()->company_id)->get();
        } else {
            $companies = Company::all();
        }

        foreach ($companies as $company) {
            if (!empty($filterBySalesPersonIds)) {
                $companyCommissions = $this->cacheCommissionRepository->allBySalesPersons($company, $timeArray, $filterBySalesPersonIds);
            } else {
                $companyCommissions = $this->cacheCommissionRepository->allByCompany($company, $timeArray);
            }

            if (!empty($companyCommissions)) {
                $resultArr[] = $companyCommissions;
                $totalAllCompaniesCommission  += $companyCommissions['total_company_commission'];
                $countTotalCommissions += $companyCommissions['count_company_commissions'];
            }
        }

        return [
          'companies' => $resultArr,
          'base_commission' => $baseCommission,
          'count_company_commissions' => $countTotalCommissions,
          'total_all_companies_commission' => $totalAllCompaniesCommission
        ];
    }
    private function getBaseCommission($salespersonId): float
    {
        $baseCommission = SalesCommission::DEFAULT_COMMISSION;

        if ($salespersonId) {
            $salesPerson = User::where('id', $salespersonId)->firstOrFail();
            if (!$salesPerson->primary_account) {
                $salesPerson = User::where([['email', $salesPerson->email], ['primary_account', true]])->first();
            }
            $latestCommission = $salesPerson->salesCommissions->sortByDesc('created_at')->first();
            if ($latestCommission) {
                $baseCommission = $latestCommission->commission;
            }
        }

        return $baseCommission;
    }

    private function getPurchaseOrdersForEarnout(int $startKey, int $endKey)
    {
        $query = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => [
                      [
                          'bool' => [
                              'must' => [
                                  'range' => [
                                      'authorised_date' => [
                                          'gte' => $startKey,
                                          'lte' => $endKey
                                      ]
                                  ],
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  'match' => ['is_contractor' => false]
                              ]
                          ]
                      ],
                  ]
              ]
          ],
          'aggs' => [
              'by_customer' => [
                  'terms' => [
                      'field' => 'customer_id',
                      'size' => 10000,
                      'order' => ['_key' => 'asc']
                  ],
                  'aggs' => [
                      'orders' => [
                          'terms' => [
                              'field' => 'order_id',
                              'size' => 10000
                          ],
                      ],
                  ],
              ],
          ],
        ];

        $result = PurchaseOrder::searchBySingleQuery($query);

        return $result['aggregations']['by_customer']['buckets'];
    }

    private function getPurchaseOrdersForPeriod(string $orderId, Company $company, int $startDate, int $endDate)
    {
        if (UserRole::isAdmin(auth()->user()->role) || $company->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_price = 'total_price';
            $total_vat = 'total_vat';
        } else {
            $total_price = 'total_price_usd';
            $total_vat = 'total_vat_usd';
        }

        $query = [
          'query' => [
              'bool' => [
                  'must' => [
                      [
                          'bool' => [
                              'must' => [
                                  'range' => [
                                      'authorised_date' => [
                                          'gte' => $startDate,
                                          'lte' => $endDate
                                      ]
                                  ],
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  'match' => ['order_id' => $orderId]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  'match' => ['is_contractor' => false]
                              ]
                          ]
                      ],
                    ],
                    'must_not' => [
                        [
                            'bool' => [
                                'must' => [
                                    'terms' => ['status' => [PurchaseOrderStatus::cancelled()->getIndex()]]
                                ]
                            ]
                        ],
                    ]
              ]
          ],
          'aggs' => [
              'total_price' => [
                  'sum' => [
                      'field' => $total_price
                  ]
              ],
              'total_vat' => [
                  'sum' => [
                      'field' => $total_vat
                  ]
              ]
          ]
        ];

        return PurchaseOrder::searchBySingleQuery($query);
    }

    private function getOrdersForPeriod(int $start, int $end, Company $company)
    {
        if (UserRole::isAdmin(auth()->user()->role) || $company->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_salary = 'project_info.external_employee_cost';
        } else {
            $total_salary = 'project_info.external_employee_cost_usd';
        }

        $query = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => [
                      [
                          'bool' => [
                              'must' => [
                                  'exists' => [
                                      'field' => 'delivered_at'
                                  ]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  'match' => ['all_invoices_paid' => true]
                              ]
                          ]
                      ],
                  ]
              ]
          ],
          'aggs' => [
              'by_customer' => [
                  'terms' => [
                      'field' => 'customer_id',
                      'size' => 10000,
                      'order' => ['_key' => 'asc']
                  ],
                  'aggs' => [
                      'orders' => [
                          'terms' => [
                              'field' => 'id',
                              'size' => 10000
                          ],
                          'aggs' => [
                              'salaries' => [
                                  'nested' => ['path' => 'project_info'],
                                  'aggs' => [
                                      'total_salary' => [
                                          'sum' => [
                                              'field' => $total_salary
                                          ]
                                      ],
                                  ]
                              ],
                              'delivered_at' => [
                                  'date_range' => [
                                      'field' => 'delivered_at',
                                      'ranges' => [
                                          'from' => $start,
                                          'to' => $end
                                      ]
                                  ]
                              ]
                          ]
                      ],
                  ],
              ]
          ]

        ];

        $result = Order::searchBySingleQuery($query);

        return $result['aggregations']['by_customer']['buckets'];
    }

    private function earnOutForStartedMonth(Company $company, int $start, int $end, int $endOfEarnOutPeriod): array
    {
        $revenueResult = $this->revenueQuery($company, $start, $end);
        $receivedRevenue = $revenueResult['aggregations']['paid']['buckets'][0]['monetary_value']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['vat_value']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['credit_notes_price']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['credit_notes_vat']['value'];

        $legacyRevenue = $revenueResult['aggregations']['paid']['buckets'][0]['legacy_customer']['monetary_value']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['legacy_customer']['vat_value']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['legacy_customer']['credit_notes_price']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['legacy_customer']['credit_notes_vat']['value'];

        $possibleRevenue = $revenueResult['aggregations']['not_paid']['possible']['buckets'][0]['monetary_value']['value'] -
          $revenueResult['aggregations']['not_paid']['possible']['buckets'][0]['vat_value']['value'];
        $possibleLegacy = $revenueResult['aggregations']['not_paid']['possible']['buckets'][0]['legacy_customer']['monetary_value']['value'] -
          $revenueResult['aggregations']['not_paid']['possible']['buckets'][0]['legacy_customer']['vat_value']['value'];

        $poCostsResult = $this->poCostsQuery($company, $start, $end);
        $authorisedCosts = $poCostsResult['aggregations']['authorised']['buckets'][0]['monetary_value']['value'] -
          $poCostsResult['aggregations']['authorised']['buckets'][0]['vat_value']['value'];
        $possibleCosts = $poCostsResult['aggregations']['not_authorised']['not_paid']['should_be_delivered']['buckets'][0]['monetary_value']['value'] -
          $poCostsResult['aggregations']['not_authorised']['not_paid']['should_be_delivered']['buckets'][0]['vat_value']['value'];

        $externalSalaryCost = $this->externalSalaryQuery($company, $start, $end);
        $deliveredSalaryCosts = $externalSalaryCost['aggregations']['delivered']['buckets'][0]['all_paid']['salaries']['total_salary']['value'];
        $possibleSalaryCosts = $externalSalaryCost['aggregations']['possible']['buckets'][0]['salaries']['total_salary']['value'];

        $rentCosts = $this->getRentsForPeriod($start, $end, $endOfEarnOutPeriod)['total_rent_costs'];
        $salaryCosts = $this->getEmployeesForPeriod($start, $end, $endOfEarnOutPeriod)['total_internal_salary'];

        $totalActual = round($receivedRevenue - $authorisedCosts - $deliveredSalaryCosts - $rentCosts - $salaryCosts, 2);
        $totalPossible = round($possibleRevenue - $possibleCosts - $possibleSalaryCosts, 2);

        return [
          'revenue' => round($receivedRevenue, 2),
          'possible_revenue' => round($possibleRevenue, 2),
          'po_costs' => round($authorisedCosts, 2),
          'possible_po_costs' => round($possibleCosts, 2),
          'external_salary_costs' => round($deliveredSalaryCosts, 2),
          'possible_external_salary_costs' => round($possibleSalaryCosts, 2),
          'monthly_costs' => round($rentCosts, 2),
          'salaries' => round($salaryCosts, 2),
          'possible_monthly_costs' => 0,
          'possible_salaries' => 0,
          'total_actual' => $totalActual,
          'total_possible' => $totalPossible,
          'monthly_total' => $totalActual + $totalPossible,
          'legacy_actual' => round($legacyRevenue, 2),
          'legacy_possible' => round($possibleLegacy, 2),
          'total_legacy' => round($legacyRevenue + $possibleLegacy, 2),
        ];
    }

    private function earnOutForEndedMonth(Company $company, int $start, int $end, int $endOfEarnOutPeriod): array
    {
        $revenueResult = $this->revenueQuery($company, $start, $end);
        $receivedRevenue = $revenueResult['aggregations']['paid']['buckets'][0]['monetary_value']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['vat_value']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['credit_notes_price']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['credit_notes_vat']['value'];

        $legacyRevenue = round($revenueResult['aggregations']['paid']['buckets'][0]['legacy_customer']['monetary_value']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['legacy_customer']['vat_value']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['legacy_customer']['credit_notes_price']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['legacy_customer']['credit_notes_vat']['value'], 2);

        $poCostsResult = $this->poCostsQuery($company, $start, $end);
        $authorisedCosts = $poCostsResult['aggregations']['authorised']['buckets'][0]['monetary_value']['value'] -
          $poCostsResult['aggregations']['authorised']['buckets'][0]['vat_value']['value'];

        $externalSalaryCost = $this->externalSalaryQuery($company, $start, $end);
        $deliveredSalaryCosts = $externalSalaryCost['aggregations']['delivered']['buckets'][0]['all_paid']['salaries']['total_salary']['value'];

        $rentCosts = $this->getRentsForPeriod($start, $end, $endOfEarnOutPeriod)['total_rent_costs'];
        $salaryCosts = $this->getEmployeesForPeriod($start, $end, $endOfEarnOutPeriod)['total_internal_salary'];

        $totalActual = round($receivedRevenue - $authorisedCosts - $deliveredSalaryCosts - $rentCosts - $salaryCosts, 2);
        $totalPossible = 0;

        return [
          'revenue' => round($receivedRevenue, 2),
          'possible_revenue' => 0,
          'po_costs' => round($authorisedCosts, 2),
          'possible_po_costs' => 0,
          'external_salary_costs' => round($deliveredSalaryCosts, 2),
          'possible_external_salary_costs' => 0,
          'monthly_costs' => round($rentCosts, 2),
          'salaries' => round($salaryCosts, 2),
          'possible_monthly_costs' => 0,
          'possible_salaries' => 0,
          'total_actual' => $totalActual,
          'total_possible' => $totalPossible,
          'monthly_total' => $totalActual + $totalPossible,
          'legacy_actual' => $legacyRevenue,
          'legacy_possible' => 0,
          'total_legacy' => $legacyRevenue,
        ];
    }

    private function earnOutForNotStartedMonth(Company $company, int $start, int $end, int $endOfEarnOutPeriod, int $month): array
    {
        $nothingToCompare = false;
        $acquisitionDate = strtotime($company->acquisition_date);

        if ($month == 1) {
            $startPreviousQuarter = strtotime('-4 months', $start);
            $endPreviousQuarter = strtotime('-1 months', $start);
        } else {
            $startPreviousQuarter = strtotime('-5 months', $start);
            $endPreviousQuarter = strtotime('-2 months', $start);
        }

        if ($startPreviousQuarter < $acquisitionDate) {
            $nothingToCompare = true;
            $startPreviousQuarter = $acquisitionDate;
            $endPreviousQuarter = strtotime('+1 month', $acquisitionDate);
        }

        $revenueResult = $this->revenueQuery($company, $startPreviousQuarter, $endPreviousQuarter);
        $receivedRevenue = round($revenueResult['aggregations']['paid']['buckets'][0]['monetary_value']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['vat_value']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['credit_notes_price']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['credit_notes_vat']['value'], 2);

        $legacyRevenue = round($revenueResult['aggregations']['paid']['buckets'][0]['legacy_customer']['monetary_value']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['legacy_customer']['vat_value']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['legacy_customer']['credit_notes_price']['value'] -
          $revenueResult['aggregations']['paid']['buckets'][0]['legacy_customer']['credit_notes_vat']['value'], 2);

        $poCostsResult = $this->poCostsQuery($company, $startPreviousQuarter, $endPreviousQuarter);
        $authorisedCosts = round($poCostsResult['aggregations']['authorised']['buckets'][0]['monetary_value']['value'] -
          $poCostsResult['aggregations']['authorised']['buckets'][0]['vat_value']['value'], 2);

        $externalSalaryCost = $this->externalSalaryQuery($company, $startPreviousQuarter, $endPreviousQuarter);
        $deliveredSalaryCosts = round($externalSalaryCost['aggregations']['delivered']['buckets'][0]['all_paid']['salaries']['total_salary']['value'], 2);

        $rentCosts = round($this->getRentsForPeriod($start, $end, $endOfEarnOutPeriod)['total_rent_costs'], 2);
        $salaryCosts = round($this->getEmployeesForPeriod($start, $end, $endOfEarnOutPeriod)['total_internal_salary'], 2);

        $totalActual = 0;
        $totalPossible = round($receivedRevenue - $authorisedCosts - $deliveredSalaryCosts - $rentCosts - $salaryCosts, 2);

        return [
          'revenue' => 0,
          'possible_revenue' => $nothingToCompare ? $receivedRevenue : $receivedRevenue / 3,
          'po_costs' => 0,
          'possible_po_costs' => $nothingToCompare ? $authorisedCosts : $authorisedCosts / 3,
          'external_salary_costs' => 0,
          'possible_external_salary_costs' => $nothingToCompare ? $deliveredSalaryCosts : $deliveredSalaryCosts / 3,
          'monthly_costs' => 0,
          'salaries' => 0,
          'possible_monthly_costs' => $rentCosts,
          'possible_salaries' => $salaryCosts,
          'total_actual' => $totalActual,
          'total_possible' => $totalPossible,
          'monthly_total' => $totalActual + $totalPossible,
          'legacy_actual' => 0,
          'legacy_possible' => $legacyRevenue,
          'total_legacy' => $legacyRevenue,
        ];
    }

    private function revenueQuery(Company $company, int $start, int $end): array
    {
        if (UserRole::isAdmin(auth()->user()->role) || $company->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_price = 'total_price';
            $total_vat = 'total_vat';
            $credit_price = 'credit_notes_total_price';
            $credit_vat = 'credit_notes_total_vat';
            $shadow_price = 'shadow_price';
            $shadow_vat = 'shadow_vat';
        } else {
            $total_price = 'total_price_usd';
            $total_vat = 'total_vat_usd';
            $credit_price = 'credit_notes_total_price_usd';
            $credit_vat = 'credit_notes_total_vat_usd';
            $shadow_price = 'shadow_price_usd';
            $shadow_vat = 'shadow_vat_usd';
        }

        $revenueQuery = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => [
                      [
                          'bool' => [
                              'must' => [
                                  ['match' => ['type' => InvoiceType::accrec()->getIndex()]]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  ['terms' => ['status' => [
                                      InvoiceStatus::draft()->getIndex(),
                                      InvoiceStatus::approval()->getIndex(), InvoiceStatus::authorised()->getIndex(),
                                      InvoiceStatus::submitted()->getIndex(), InvoiceStatus::paid()->getIndex(), InvoiceStatus::partial_paid()->getIndex()
                                  ]]]
                              ]
                          ]
                      ],
                  ],
              ]
          ],
          'aggs' => [
              'paid' => [
                  'date_range' => [
                      'field' => 'pay_date',
                      'ranges' => [
                          'from' => $start,
                          'to' => $end
                      ]
                  ],
                  'aggs' => [
                      'monetary_value' => [
                          'sum' => [
                              'script' => [
                                  'lang' => 'painless',
                                  'inline' => "doc['$total_price'].value - doc['$shadow_price'].value"
                              ]
                          ]
                      ],
                      'vat_value' => [
                          'sum' => [
                              'script' => [
                                  'lang' => 'painless',
                                  'inline' => "doc['$total_vat'].value - doc['$shadow_vat'].value"
                              ]
                          ]
                      ],
                      'credit_notes_price' => [
                          'sum' => [
                              'field' => $credit_price
                          ]
                      ],
                      'credit_notes_vat' => [
                          'sum' => [
                              'field' => $credit_vat
                          ]
                      ],
                      'legacy_customer' => [
                          'filter' => [
                              'term' => ['legacy_customer' => true]
                          ],
                          'aggs' => [
                              'monetary_value' => [
                                  'sum' => [
                                      'script' => [
                                          'lang' => 'painless',
                                          'inline' => "doc['$total_price'].value - doc['$shadow_price'].value"
                                      ]
                                  ]
                              ],
                              'vat_value' => [
                                  'sum' => [
                                      'script' => [
                                          'lang' => 'painless',
                                          'inline' => "doc['$total_vat'].value - doc['$shadow_vat'].value"
                                      ]
                                  ]
                              ],
                              'credit_notes_price' => [
                                  'sum' => [
                                      'field' => $credit_price
                                  ]
                              ],
                              'credit_notes_vat' => [
                                  'sum' => [
                                      'field' => $credit_vat
                                  ]
                              ],
                          ]
                      ],
                  ]
              ],
              'not_paid' => [
                  'missing' => ['field' => 'pay_date'],
                  'aggs' => [
                      'possible' => [
                          'date_range' => [
                              'field' => 'due_date',
                              'ranges' => [
                                  'from' => $start,
                                  'to' => $end
                              ]
                          ],
                          'aggs' => [
                              'monetary_value' => [
                                  'sum' => [
                                      'script' => [
                                          'lang' => 'painless',
                                          'inline' => "doc['$total_price'].value - doc['$shadow_price'].value"
                                      ]
                                  ]
                              ],
                              'vat_value' => [
                                  'sum' => [
                                      'script' => [
                                          'lang' => 'painless',
                                          'inline' => "doc['$total_vat'].value - doc['$shadow_vat'].value"
                                      ]
                                  ]
                              ],
                              'legacy_customer' => [
                                  'filter' => [
                                      'term' => ['legacy_customer' => true]
                                  ],
                                  'aggs' => [
                                      'monetary_value' => [
                                          'sum' => [
                                              'script' => [
                                                  'lang' => 'painless',
                                                  'inline' => "doc['$total_price'].value - doc['$shadow_price'].value"
                                              ]
                                          ]
                                      ],
                                      'vat_value' => [
                                          'sum' => [
                                              'script' => [
                                                  'lang' => 'painless',
                                                  'inline' => "doc['$total_vat'].value - doc['$shadow_vat'].value"
                                              ]
                                          ]
                                      ],
                                  ]
                              ],
                          ]
                      ]
                  ]
              ],
          ]
        ];

        return Invoice::searchBySingleQuery($revenueQuery);
    }

    private function poCostsQuery(Company $company, int $start, int $end): array
    {
        if (UserRole::isAdmin(auth()->user()->role) || $company->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_price = 'total_price';
            $total_vat = 'total_vat';
        } else {
            $total_price = 'total_price_usd';
            $total_vat = 'total_vat_usd';
        }

        $query = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => [
                      ['terms' => ['status' => [
                          PurchaseOrderStatus::draft()->getIndex(),
                          PurchaseOrderStatus::submitted()->getIndex(), PurchaseOrderStatus::authorised()->getIndex(),
                          PurchaseOrderStatus::billed()->getIndex(), PurchaseOrderStatus::paid()->getIndex()
                      ]]]
                  ]
              ]
          ],
          'aggs' => [
              'authorised' => [
                  'date_range' => [
                      'field' => 'authorised_date',
                      'ranges' => [
                          'from' => $start,
                          'to' => $end
                      ]
                  ],
                  'aggs' => [
                      'monetary_value' => [
                          'sum' => [
                              'field' => $total_price
                          ]
                      ],
                      'vat_value' => [
                          'sum' => [
                              'field' => $total_vat
                          ]
                      ]
                  ]
              ],
              'not_authorised' => [
                  'missing' => [
                      'field' => 'authorised_date'
                  ],
                  'aggs' => [
                      'not_paid' => [
                          'missing' => [
                              'field' => 'pay_date'
                          ],
                          'aggs' => [
                              'should_be_delivered' => [
                                  'date_range' => [
                                      'field' => 'delivery_date',
                                      'ranges' => [
                                          'from' => $start,
                                          'to' => $end
                                      ]
                                  ],
                                  'aggs' => [
                                      'monetary_value' => [
                                          'sum' => [
                                              'field' => $total_price
                                          ]
                                      ],
                                      'vat_value' => [
                                          'sum' => [
                                              'field' => $total_vat
                                          ]
                                      ]
                                  ]
                              ],
                          ]
                      ]
                  ],
              ]
          ],
        ];

        return PurchaseOrder::searchBySingleQuery($query);
    }

    private function externalSalaryQuery(Company $company, int $start, int $end): array
    {
        if (UserRole::isAdmin(auth()->user()->role) || $company->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_salary = 'project_info.external_employee_cost';
        } else {
            $total_salary = 'project_info.external_employee_cost_usd';
        }

        $query = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => [
                      ['terms' => ['status' => [
                          OrderStatus::active()->getIndex(),
                          OrderStatus::delivered()->getIndex(), OrderStatus::invoiced()->getIndex()
                      ]]]
                  ]
              ]
          ],
          'aggs' => [
              'delivered' => [
                  'date_range' => [
                      'field' => 'delivered_at',
                      'ranges' => [
                          'from' => $start,
                          'to' => $end
                      ]
                  ],
                  'aggs' => [
                      'all_paid' => [
                          'filter' => [
                              'term' => ['all_invoices_paid' => true]
                          ],
                          'aggs' => [
                              'salaries' =>  [
                                  'nested' => ['path' => 'project_info'],
                                  'aggs' => [
                                      'total_salary' => [
                                          'sum' => [
                                              'field' => $total_salary
                                          ]
                                      ],
                                  ]
                              ],
                          ]
                      ]
                  ]
              ],
              'possible' => [
                  'date_range' => [
                      'field' => 'deadline',
                      'ranges' => [
                          'from' => $start,
                          'to' => $end
                      ]
                  ],
                  'aggs' => [
                      'salaries' =>  [
                          'nested' => ['path' => 'project_info'],
                          'aggs' => [
                              'total_salary' => [
                                  'sum' => [
                                      'field' => $total_salary
                                  ]
                              ],
                          ]
                      ],
                  ]
              ]
          ]
        ];

        return Order::searchBySingleQuery($query);
    }

    private function getPurchaseOrdersWithoutOrders(Company $company, int $startDate, int $endDate)
    {
        if (UserRole::isAdmin(auth()->user()->role) || $company->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_price = 'total_price';
            $total_vat = 'total_vat';
        } else {
            $total_price = 'total_price_usd';
            $total_vat = 'total_vat_usd';
        }

        $query = [
          'query' => [
              'bool' => [
                  'must' => [
                      [
                          'bool' => [
                              'must' => [
                                  'range' => [
                                      'authorised_date' => [
                                          'gte' => $startDate,
                                          'lte' => $endDate
                                      ]
                                  ],
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  'match' => ['purchase_order_project' => true]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  'match' => ['is_contractor' => false]
                              ]
                          ]
                      ],
                  ]
              ]
          ],
          'aggs' => [
              'total_price' => [
                  'sum' => [
                      'field' => $total_price
                  ]
              ],
              'total_vat' => [
                  'sum' => [
                      'field' => $total_vat
                  ]
              ],
          ]
        ];

        return PurchaseOrder::searchBySingleQuery($query);
    }
}
