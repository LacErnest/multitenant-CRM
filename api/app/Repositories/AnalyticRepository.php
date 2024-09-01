<?php


namespace App\Repositories;


use App\Contracts\Repositories\EarnOutStatusRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyLoan;
use App\Models\CompanyRent;
use App\Models\Customer;
use App\Models\EmployeeHistory;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tenancy\Environment;
use Tenancy\Facades\Tenancy;

class AnalyticRepository
{
    public function get($day, $week, $month, $quarter, $year)
    {
        $draftQuoteCount = $draftQuotePrice = $draftQuoteVat = $awaitingQuoteCount = $awaitingQuotePrice =
          $awaitingQuoteVat = $declinedQuoteCount = $declinedQuotePrice = $declinedQuoteVat = 0;
        $draftOrderCount = $draftOrderPrice = $draftOrderVat = $activeOrderCount = $activeOrderPrice = $activeOrderVat =
          $deliveredOrderCount = $deliveredOrderPrice = $deliveredOrderVat = 0;
        $draftPoCount = $draftPoPrice = $draftPoVat = $awaitingPoCount = $awaitingPoPrice =
          $awaitingPoVat = $paidPoCount = $paidPoPrice = $paidPoVat = 0;
        $draftInvoiceCount = $draftInvoicePrice = $draftInvoiceVat = $awaitingInvoiceCount = $awaitingInvoicePrice =
          $awaitingInvoiceVat = $paidInvoiceCount = $paidInvoicePrice = $paidInvoiceVat = $approvedInvoiceCount =
          $approvedInvoicePrice = $approvedInvoiceVat = 0;

        $array = $this->getStartAndEnd($day, $week, $month, $quarter, $year);
        $interval = $array['interval'];
        $week = $array['week'];


        /** all quotes */
        $terms = [QuoteStatus::draft()->getIndex(), QuoteStatus::sent()->getIndex(), QuoteStatus::declined()->getIndex()];
        $noDrafts = [QuoteStatus::sent()->getIndex(), QuoteStatus::ordered()->getIndex(), QuoteStatus::invoiced()->getIndex()];
        $mustNotInclude = [QuoteStatus::cancelled()->getIndex()];
        $quotesQuery = $this->getQuery(Quote::class, $array, $terms, $noDrafts, $mustNotInclude, false, QuoteStatus::class);

        $quotes = Quote::searchAllTenantsQuery('quotes', $quotesQuery);

        $quoteStats = $this->getEntityStats($quotes, QuoteStatus::class);
        $quoteStatsByStatus = $quoteStats['default']['entities_by_status'];
        $quotes_per_interval = $quoteStats['all']['total_entities'];
        $quotes_year_before = $quoteStats['all']['year_before'];
        $quotes_current_year = $quoteStats['all']['current_year'];
        $quotes_intra_company_current_year = $quoteStats['all']['intra_company_current_year'];
        $quotes_intra_company_year_before = $quoteStats['all']['intra_company_year_before'];

        $draftQuoteCount = $quoteStatsByStatus[QuoteStatus::draft()->getName()]['doc_count'];
        $draftQuotePrice = $quoteStatsByStatus[QuoteStatus::draft()->getName()]['monetary_value'];
        $draftQuoteVat = $quoteStatsByStatus[QuoteStatus::draft()->getName()]['vat_value'];

        $awaitingQuoteCount = $quoteStatsByStatus[QuoteStatus::sent()->getName()]['doc_count'];
        $awaitingQuotePrice = $quoteStatsByStatus[QuoteStatus::sent()->getName()]['monetary_value'];
        $awaitingQuoteVat = $quoteStatsByStatus[QuoteStatus::sent()->getName()]['vat_value'];

        $declinedQuoteCount = $quoteStatsByStatus[QuoteStatus::declined()->getName()]['doc_count'];
        $declinedQuotePrice = $quoteStatsByStatus[QuoteStatus::declined()->getName()]['monetary_value'];
        $declinedQuoteVat = $quoteStatsByStatus[QuoteStatus::declined()->getName()]['vat_value'];

        /** all orders */
        $terms = [OrderStatus::draft()->getIndex(), OrderStatus::active()->getIndex(), OrderStatus::invoiced()->getIndex(), OrderStatus::delivered()->getIndex()];
        $noDrafts = [OrderStatus::active()->getIndex(), OrderStatus::invoiced()->getIndex(), OrderStatus::delivered()->getIndex()];
        $mustNotInclude = [OrderStatus::cancelled()->getIndex()];
        $ordersQuery = $this->getQuery(Order::class, $array, $terms, $noDrafts, $mustNotInclude, false, OrderStatus::class);

        $orders = Order::searchAllTenantsQuery('orders', $ordersQuery);

        $orderStats = $this->getEntityStats($orders, OrderStatus::class);
        $orderStatsByStatus = $orderStats['default']['entities_by_status'];
        $orders_per_interval = $orderStats['all']['total_entities'];
        $orders_year_before = $orderStats['all']['year_before'];
        $orders_current_year = $orderStats['all']['current_year'];
        $orders_intra_company_current_year = $orderStats['all']['intra_company_current_year'];
        $orders_intra_company_year_before = $orderStats['all']['intra_company_year_before'];

        $draftOrderCount = $orderStatsByStatus[OrderStatus::draft()->getName()]['doc_count'];
        $draftOrderPrice = $orderStatsByStatus[OrderStatus::draft()->getName()]['monetary_value'];
        $draftOrderVat = $orderStatsByStatus[OrderStatus::draft()->getName()]['vat_value'];

        $activeOrderCount = $orderStatsByStatus[OrderStatus::active()->getName()]['doc_count'];
        $activeOrderPrice = $orderStatsByStatus[OrderStatus::active()->getName()]['monetary_value'];
        $activeOrderVat = $orderStatsByStatus[OrderStatus::active()->getName()]['vat_value'];

        $deliveredOrderCount = $orderStatsByStatus[OrderStatus::delivered()->getName()]['doc_count'] +
          $orderStatsByStatus[OrderStatus::invoiced()->getName()]['doc_count'];
        $deliveredOrderPrice = $orderStatsByStatus[OrderStatus::delivered()->getName()]['monetary_value'] +
          $orderStatsByStatus[OrderStatus::invoiced()->getName()]['monetary_value'];
        $deliveredOrderVat = $orderStatsByStatus[OrderStatus::delivered()->getName()]['vat_value'] +
          $orderStatsByStatus[OrderStatus::invoiced()->getName()]['vat_value'];

        /** all purchase orders */
        $terms = [PurchaseOrderStatus::draft()->getIndex(), PurchaseOrderStatus::billed()->getIndex(), PurchaseOrderStatus::paid()->getIndex()];
        $noDrafts = [
          PurchaseOrderStatus::submitted()->getIndex(), PurchaseOrderStatus::completed()->getIndex(),
          PurchaseOrderStatus::authorised()->getIndex(), PurchaseOrderStatus::billed()->getIndex(), PurchaseOrderStatus::paid()->getIndex()
        ];
        $mustNotInclude = [PurchaseOrderStatus::cancelled()->getIndex(), PurchaseOrderStatus::rejected()->getIndex()];

        $purchaseQuery = $this->getQuery(PurchaseOrder::class, $array, $terms, $noDrafts, $mustNotInclude, true, PurchaseOrderStatus::class);

        $purchases = PurchaseOrder::searchAllTenantsQuery('purchase_orders', $purchaseQuery);

        $purchaseStats = $this->getEntityStats($purchases, PurchaseOrderStatus::class);
        $purchaseStatsByStatus = $purchaseStats['default']['entities_by_status'];
        $purchases_per_interval = $purchaseStats['all']['total_entities'];
        $purchases_year_before = $purchaseStats['all']['year_before'];
        $purchases_current_year = $purchaseStats['all']['current_year'];
        $purchases_intra_company_current_year = $purchaseStats['all']['intra_company_current_year'];
        $purchases_intra_company_year_before = $purchaseStats['all']['intra_company_year_before'];

        $draftPoCount = $purchaseStatsByStatus[PurchaseOrderStatus::draft()->getName()]['doc_count'];
        $draftPoPrice = $purchaseStatsByStatus[PurchaseOrderStatus::draft()->getName()]['monetary_value'];
        $draftPoVat = $purchaseStatsByStatus[PurchaseOrderStatus::draft()->getName()]['vat_value'];

        $awaitingPoCount = $purchaseStatsByStatus[PurchaseOrderStatus::billed()->getName()]['doc_count'];
        $awaitingPoPrice = $purchaseStatsByStatus[PurchaseOrderStatus::billed()->getName()]['monetary_value'];
        $awaitingPoVat = $purchaseStatsByStatus[PurchaseOrderStatus::billed()->getName()]['vat_value'];

        $paidPoCount = $purchaseStatsByStatus[PurchaseOrderStatus::paid()->getName()]['doc_count'];
        $paidPoPrice = $purchaseStatsByStatus[PurchaseOrderStatus::paid()->getName()]['monetary_value'];
        $paidPoVat = $purchaseStatsByStatus[PurchaseOrderStatus::paid()->getName()]['vat_value'];

        /** all invoices */
        $terms = [
          InvoiceStatus::draft()->getIndex(), InvoiceStatus::submitted()->getIndex(), InvoiceStatus::paid()->getIndex(), InvoiceStatus::partial_paid()->getIndex(),
          InvoiceStatus::approval()->getIndex(), InvoiceStatus::authorised()->getIndex(), InvoiceStatus::unpaid()->getIndex()
        ];
        $noDrafts = [InvoiceStatus::submitted()->getIndex(), InvoiceStatus::paid()->getIndex(), InvoiceStatus::partial_paid()->getIndex()];
        $invoicesQuery = $this->getInvoiceQuery($array, $terms, $noDrafts);

        $invoices = Invoice::searchAllTenantsQuery('invoices', $invoicesQuery);

        $invoiceStats = $this->getEntityStats($invoices, InvoiceStatus::class);
        $invoiceStatsByStatus = $invoiceStats['default']['entities_by_status'];
        $invoices_per_interval = $invoiceStats['all']['total_entities'];
        $invoices_year_before = $invoiceStats['all']['year_before'];
        $invoices_current_year = $invoiceStats['all']['current_year'];

        $intraCompanyDocs = $invoices['aggregations']['intra_company'];
        $intraInvoiceCount = $intraCompanyDocs['doc_count'];
        $intraInvoicePrice = $intraCompanyDocs['monetary_value']['value'];
        $intraInvoiceVat = $intraCompanyDocs['vat_value']['value'];

        $invoiceOverdueDocs = $this->getOverduesStats($invoices['aggregations']);

        $draftInvoiceCount = $invoiceStatsByStatus[InvoiceStatus::draft()->getName()]['doc_count'];
        $draftInvoicePrice = $invoiceStatsByStatus[InvoiceStatus::draft()->getName()]['monetary_value'];
        $draftInvoiceVat = $invoiceStatsByStatus[InvoiceStatus::draft()->getName()]['vat_value'];

        $awaitingInvoiceCount = $invoiceStatsByStatus[InvoiceStatus::submitted()->getName()]['doc_count'] +
        $invoiceStatsByStatus[InvoiceStatus::partial_paid()->getName()]['doc_count'];
        $awaitingInvoicePrice = $invoiceStatsByStatus[InvoiceStatus::submitted()->getName()]['monetary_value'] +
          $invoiceStatsByStatus[InvoiceStatus::partial_paid()->getName()]['monetary_value'];
        $awaitingInvoiceVat = $invoiceStatsByStatus[InvoiceStatus::submitted()->getName()]['vat_value'] +
          $invoiceStatsByStatus[InvoiceStatus::partial_paid()->getName()]['vat_value'];

        $paidInvoiceCount = $invoiceStatsByStatus[InvoiceStatus::paid()->getName()]['doc_count'] +
          $invoiceStatsByStatus[InvoiceStatus::partial_paid()->getName()]['doc_count'];
        $paidInvoicePrice = $invoiceStatsByStatus[InvoiceStatus::paid()->getName()]['monetary_value'] +
          $invoiceStatsByStatus[InvoiceStatus::partial_paid()->getName()]['monetary_value'];
        $paidInvoiceVat = $invoiceStatsByStatus[InvoiceStatus::paid()->getName()]['vat_value'] +
          $invoiceStatsByStatus[InvoiceStatus::partial_paid()->getName()]['monetary_value'];

        $approvedInvoiceCount = $invoiceStatsByStatus[InvoiceStatus::authorised()->getName()]['doc_count'] +
          $invoiceStatsByStatus[InvoiceStatus::approval()->getName()]['doc_count'];
        $approvedInvoicePrice = $invoiceStatsByStatus[InvoiceStatus::authorised()->getName()]['monetary_value'] +
          $invoiceStatsByStatus[InvoiceStatus::approval()->getName()]['monetary_value'];
        $approvedInvoiceVat = $invoiceStatsByStatus[InvoiceStatus::authorised()->getName()]['vat_value'] +
          $invoiceStatsByStatus[InvoiceStatus::approval()->getName()]['monetary_value'];


        /** earnouts */
        $data = [];
        $endDate = time();
        $startDate = strtotime('-3 years', getBeginningOfQuarter(now()));
        foreach (Company::all() as $company) {
            if (!$company->acquisition_date || $company->earnout_years <= 0) {
                continue;
            } else {
                $loanAmountBeforePeriod = 0;
                Tenancy::setTenant($company);
                $earnoutsQuery = $this->getOrdersForEarnoutQuery($startDate, $endDate);
                $earnouts = Invoice::searchBySingleQuery($earnoutsQuery);
                $orderIdsByInterval = $earnouts['aggregations']['total_earnouts']['buckets'][0]['earnouts_per']['buckets'];

                $loans = CompanyLoan::whereDate('issued_at', '<', date('Y-m-d', $startDate))
                ->orderBy('issued_at')->get();

                if ($loans->isNotEmpty()) {
                    foreach ($loans as $loan) {
                        $loanAmountBeforePeriod += UserRole::isAdmin(auth()->user()->role) ? $loan->admin_amount : $loan->amount;
                    }

                    $loan = $loans->first();
                    if ($loan) {
                        $startOfQuarter = getBeginningOfQuarter($loan->issued_at);
                        $earnoutsArray = $this->getEarnoutDataForPeriod($startOfQuarter, $startDate, $company, false);
                        $earnoutsLoanBeforeStart = $earnoutsArray['earnout_bonus'] + $earnoutsArray['gross_margin_bonus'];
                        $loanAmountBeforePeriod -= $earnoutsLoanBeforeStart;
                        if ($loanAmountBeforePeriod < 0) {
                            $loanAmountBeforePeriod = 0;
                        }
                    }
                }

                $data = $this->getEarnoutSeries($orderIdsByInterval, $company, $data, $loanAmountBeforePeriod);
            }
        }


        $resultArray = [
          'quotes' => [
              'chosen_period' => [
                  'total_quotes' => [
                      'count' => $quotes['aggregations']['all']['total_entities']['doc_count'] ?? 0,
                      'monetary_value' => round($quotes['aggregations']['all']['total_entities']['monetary_value']['value'] ?? 0, 2),
                      'vat_value' => round($quotes['aggregations']['all']['total_entities']['vat_value']['value'] ?? 0, 2),
                  ],
                  'drafts' => [
                      'count' => $draftQuoteCount,
                      'monetary_value' => round($draftQuotePrice, 2),
                      'vat_value' => round($draftQuoteVat, 2)
                  ],
                  'awaiting_approval' => [
                      'count' => $awaitingQuoteCount,
                      'monetary_value' => round($awaitingQuotePrice, 2),
                      'vat_value' => round($awaitingQuoteVat, 2)
                  ],
                  'declined' => [
                      'count' => $declinedQuoteCount,
                      'monetary_value' => round($declinedQuotePrice, 2),
                      'vat_value' => round($declinedQuoteVat, 2)
                  ],
                  'data' => $this->getSeries($quotes_current_year, $quotes_intra_company_current_year, $interval, $week, true)
              ],
              'year_before' => $this->getSeries($quotes_year_before, $quotes_intra_company_year_before, $interval, $week, true, true)
          ],
          'orders' => [
              'chosen_period' => [
                  'total_orders' => [
                      'count' => $orders['aggregations']['all']['total_entities']['doc_count'] ?? 0,
                      'monetary_value' => round($orders['aggregations']['all']['total_entities']['monetary_value']['value'] ?? 0, 2),
                      'vat_value' => round($orders['aggregations']['all']['total_entities']['vat_value']['value'] ?? 0, 2),
                  ],
                  'drafts' => [
                      'count' => $draftOrderCount,
                      'monetary_value' => round($draftOrderPrice, 2),
                      'vat_value' => round($draftOrderVat, 2)
                  ],
                  'active' => [
                      'count' => $activeOrderCount,
                      'monetary_value' => round($activeOrderPrice, 2),
                      'vat_value' => round($activeOrderVat, 2)
                  ],
                  'delivered' => [
                      'count' => $deliveredOrderCount,
                      'monetary_value' => round($deliveredOrderPrice, 2),
                      'vat_value' => round($deliveredOrderVat, 2)
                  ],
                  'data' => $this->getSeries($orders_current_year, $orders_intra_company_current_year, $interval, $week, true)
              ],
              'year_before' => $this->getSeries($orders_year_before, $orders_intra_company_year_before, $interval, $week, true, true)
          ],
          'purchase_orders' => [
              'chosen_period' => [
                  'total_purchase_orders' => [
                      'count' => $purchases['aggregations']['all']['total_entities']['doc_count'] ?? 0,
                      'monetary_value' => round($purchases['aggregations']['all']['total_entities']['monetary_value']['value'] ?? 0, 2),
                      'vat_value' => round($purchases['aggregations']['all']['total_entities']['vat_value']['value'] ?? 0, 2),
                  ],
                  'drafts' => [
                      'count' => $draftPoCount,
                      'monetary_value' => round($draftPoPrice, 2),
                      'vat_value' => round($draftPoVat, 2)
                  ],
                  'awaiting_payment' => [
                      'count' => $awaitingPoCount,
                      'monetary_value' => round($awaitingPoPrice, 2),
                      'vat_value' => round($awaitingPoVat, 2)
                  ],
                  'paid' => [
                      'count' => $paidPoCount,
                      'monetary_value' => round($paidPoPrice, 2),
                      'vat_value' => round($paidPoVat, 2)
                  ],
                  'data' => [
                      (object)[
                          'name' => 'purchase_orders',
                          'series' => $this->getSeries($purchases_current_year, $purchases_intra_company_current_year, $interval, $week)
                      ]
                  ]
              ],
              'year_before' => [
                  (object)[
                      'name' => 'purchase_orders_prev',
                      'series' => $this->getSeries($purchases_year_before, $purchases_intra_company_year_before, $interval, $week)
                  ]
              ]
          ],
          'invoices' => [
              'chosen_period' => [
                  'total_invoices' => [
                      'count' => $invoices['aggregations']['all']['total_entities']['doc_count'] ?? 0,
                      'monetary_value' => round($invoices['aggregations']['all']['total_entities']['monetary_value']['value'] ?? 0, 2),
                      'vat_value' => round($invoices['aggregations']['all']['total_entities']['vat_value']['value'] ?? 0, 2),
                  ],
                  'drafts' => [
                      'count' => $draftInvoiceCount,
                      'monetary_value' => round($draftInvoicePrice, 2),
                      'vat_value' => round($draftInvoiceVat, 2)
                  ],
                  'awaiting_payment' => [
                      'count' => $awaitingInvoiceCount,
                      'monetary_value' => round($awaitingInvoicePrice, 2),
                      'vat_value' => round($awaitingInvoiceVat, 2)
                  ],
                  'paid' => [
                      'count' => $paidInvoiceCount,
                      'monetary_value' => round($paidInvoicePrice, 2),
                      'vat_value' => round($paidInvoiceVat, 2)
                  ],
                  'approved_authorised' => [
                      'count' => $approvedInvoiceCount,
                      'monetary_value' => round($approvedInvoicePrice, 2),
                      'vat_value' => round($approvedInvoiceVat, 2)
                  ],
                  'overdue' => [
                      'count' => $invoiceOverdueDocs['doc_count'],
                      'vat_value' => $invoiceOverdueDocs['vat_value'],
                      'monetary_value' => $invoiceOverdueDocs['monetary_value']
                  ],
                  'intra_company' => [
                      'count' => $intraInvoiceCount,
                      'vat_value' => $intraInvoiceVat,
                      'monetary_value' => $intraInvoicePrice
                  ],
                  'data' => $this->getInvoiceSeries($invoices_current_year, $interval, $week, false)
              ],
              'year_before' => $this->getInvoiceSeries($invoices_year_before, $interval, $week, false, true)
          ],
          'earnouts' => [
              'chosen_period' => [
                  'data' => $data,
              ]
          ]
        ];

        return $resultArray;
    }

    /**
     * Extract entities stats from query results
     * @param array $queryResult
     * @param mixed $status
     * @return array
     */
    private function getEntityStats(array $queryResult, $entityStatus, $withIntraCompany = false)
    {
        $globalStats = [];
        $statusFiels = ['intra_company_entities_by_status','entities_by_status'];
        $historyFiels = ['normal_total_entities','year_before','total_entities','current_year', 'intra_company_current_year','intra_company_year_before'];
        $groups = ['by_creation_date','by_date','by_payment_date'];
        $aggregationKeys = ['all', 'default'];

        foreach ($aggregationKeys as $aggregationKey) {
            foreach ($queryResult['aggregations'][$aggregationKey] as $extractorKey => $mainBucket) {
                $stats = ['doc_count' => 0, 'monetary_value' => 0, 'vat_value' => 0];

                if ($extractorKey =='total_entities' && empty($mainBucket['buckets'])) {
                    $mainBucket['buckets'][] = $mainBucket;
                } elseif ( in_array($extractorKey, ['year_before','current_year', 'intra_company_current_year','intra_company_year_before']) && empty($mainBucket['buckets'])) {
                    $mainBucket['buckets'][] = $mainBucket;
                }

                if (empty($mainBucket['buckets'])) {
                    continue;
                }

                foreach ($mainBucket['buckets'] as $field => $subBucket) {
                    if (!isset($globalStats[$aggregationKey][$extractorKey])) {
                        $globalStats[$aggregationKey][$extractorKey] = [];
                    }
                    if (in_array($extractorKey, $historyFiels) && isset($subBucket['entities_per']['buckets'])) {
                        $globalStats[$aggregationKey][$extractorKey] = array_merge($globalStats[$aggregationKey][$extractorKey], $subBucket['entities_per']['buckets']);
                    } elseif (in_array($extractorKey, $statusFiels)) {
                        if (isset($subBucket['term_entities']['buckets'])) {
                                  $buckets = $subBucket['term_entities']['buckets'];
                        } elseif (isset($subBucket['term_entities']['by_status']['buckets'])) {
                              $buckets = $subBucket['term_entities']['by_status']['buckets'];
                        } else {
                                $buckets = [];
                        }
                        foreach ($buckets as $bucket) {
                              $statusIndex = (int) $bucket['key'];
                              $statusName = $entityStatus::make($statusIndex)->getName();
                            if (!isset($stats[$statusName])) {
                                $stats[$statusName]['doc_count'] = 0;
                                $stats[$statusName]['monetary_value'] = 0;
                                $stats[$statusName]['vat_value'] = 0;
                            }
                              $stats[$statusName]['doc_count'] += $bucket['doc_count'];
                            if ($statusName == InvoiceStatus::partial_paid()->getName()) {
                                $stats[$statusName]['monetary_value'] += $bucket['partial_monetary_value']['value'];
                                $stats[$statusName]['vat_value'] += $bucket['partial_vat_value']['value'];
                            } else {
                                $stats[$statusName]['monetary_value'] += $bucket['monetary_value']['value'];
                                $stats[$statusName]['vat_value'] += $bucket['vat_value']['value'];
                            }

                              $stats['doc_count'] += $bucket['doc_count'];
                              $stats['monetary_value'] += $bucket['monetary_value']['value'];
                              $stats['vat_value'] += $bucket['vat_value']['value'];
                        }
                    }
                }
                if (in_array($extractorKey, $statusFiels)) {
                    foreach ($entityStatus::getNames() as $statusName) {
                        if (!isset($stats[$statusName])) {
                              $stats[$statusName]['doc_count'] = 0;
                              $stats[$statusName]['monetary_value'] = 0;
                              $stats[$statusName]['vat_value'] = 0;
                        }
                    }
                    $globalStats[$aggregationKey][$extractorKey] = $stats;
                }
            }
        }

        return $globalStats;
    }

    private function getOverduesStats($queryResult): array
    {
        $termEntities = $queryResult['all']['total_overdues']['term_entities'] ?? [];
        $buckets = $termEntities['not_paid']['only_sent']['overdue']['buckets'] ?? [];
        $overdues = ['doc_count' => 0, 'monetary_value' => 0, 'vat_value' => 0];
        foreach ($buckets as $bucket) {
            if ((bool) $bucket['key'] == true) {
                $overdues['doc_count'] = $bucket['doc_count'];
                $overdues['monetary_value'] = $bucket['monetary_value']['value'];
                $overdues['vat_value'] = $bucket['vat_value']['value'];
                break;
            }
        }
        return $overdues;
    }

    public function getCompany($company_id, $day, $week, $month, $quarter, $year)
    {
        $draftQuoteCount = $draftQuotePrice = $draftQuoteVat = $awaitingQuoteCount = $awaitingQuotePrice =
          $awaitingQuoteVat = $declinedQuoteCount = $declinedQuotePrice = $declinedQuoteVat = 0;
        $draftOrderCount = $draftOrderPrice = $draftOrderVat = $activeOrderCount = $activeOrderPrice = $activeOrderVat =
          $deliveredOrderCount = $deliveredOrderPrice = $deliveredOrderVat = 0;
        $draftPoCount = $draftPoPrice = $draftPoVat = $awaitingPoCount = $awaitingPoPrice =
          $awaitingPoVat = $paidPoCount = $paidPoPrice = $paidPoVat = 0;
        $draftInvoiceCount = $draftInvoicePrice = $draftInvoiceVat = $awaitingInvoiceCount = $awaitingInvoicePrice =
          $awaitingInvoiceVat = $paidInvoiceCount = $paidInvoicePrice = $paidInvoiceVat = $approvedInvoiceCount =
          $approvedInvoicePrice = $approvedInvoiceVat = 0;
        $intraCompanyInvoiceCount = $intraCompanyInvoicePrice = $intraCompanyInvoiceVat = 0;

        $company = Company::findOrFail($company_id);
        $array = $this->getStartAndEnd($day, $week, $month, $quarter, $year);
        $interval = $array['interval'];
        $week = $array['week'];


        /** all quotes */
        $terms = [QuoteStatus::draft()->getIndex(), QuoteStatus::sent()->getIndex(), QuoteStatus::declined()->getIndex(), QuoteStatus::invoiced()->getIndex(), QuoteStatus::cancelled()->getIndex()];
        $noDrafts = [QuoteStatus::sent()->getIndex(), QuoteStatus::ordered()->getIndex(), QuoteStatus::declined()->getIndex(), QuoteStatus::invoiced()->getIndex(), QuoteStatus::cancelled()->getIndex()];
        $mustNotInclude = [QuoteStatus::cancelled()->getIndex()];
        $quotesQuery = $this->getQuery(Quote::class, $array, $terms, $noDrafts, $mustNotInclude, false, QuoteStatus::class);

        $quotes = Quote::searchBySingleQuery($quotesQuery);

        $quoteStats = $this->getEntityStats($quotes, QuoteStatus::class);
        $quoteStatsByStatus = $quoteStats['default']['entities_by_status'];
        $quotes_per_interval = $quoteStats['default']['total_entities'];
        $quotes_year_before = $quoteStats['all']['year_before'];
        $quotes_current_year = $quoteStats['all']['current_year'];
        $quotes_intra_company_current_year = $quoteStats['all']['intra_company_current_year'];
        $quotes_intra_company_year_before = $quoteStats['all']['intra_company_year_before'];

        $draftQuoteCount = $quoteStatsByStatus[QuoteStatus::draft()->getName()]['doc_count'];
        $draftQuotePrice = $quoteStatsByStatus[QuoteStatus::draft()->getName()]['monetary_value'];
        $draftQuoteVat = $quoteStatsByStatus[QuoteStatus::draft()->getName()]['vat_value'];

        $awaitingQuoteCount = $quoteStatsByStatus[QuoteStatus::sent()->getName()]['doc_count'];
        $awaitingQuotePrice = $quoteStatsByStatus[QuoteStatus::sent()->getName()]['monetary_value'];
        $awaitingQuoteVat = $quoteStatsByStatus[QuoteStatus::sent()->getName()]['vat_value'];

        $declinedQuoteCount = $quoteStatsByStatus[QuoteStatus::declined()->getName()]['doc_count'];
        $declinedQuotePrice = $quoteStatsByStatus[QuoteStatus::declined()->getName()]['monetary_value'];
        $declinedQuoteVat = $quoteStatsByStatus[QuoteStatus::declined()->getName()]['vat_value'];

        /** all orders */
        $terms = [OrderStatus::draft()->getIndex(), OrderStatus::active()->getIndex(), OrderStatus::invoiced()->getIndex(), OrderStatus::delivered()->getIndex()];
        $noDrafts = [OrderStatus::active()->getIndex(), OrderStatus::invoiced()->getIndex(), OrderStatus::delivered()->getIndex(), OrderStatus::cancelled()->getIndex()];
        $mustNotInclude = [OrderStatus::cancelled()->getIndex()];
        $ordersQuery = $this->getQuery(Order::class, $array, $terms, $noDrafts, $mustNotInclude, false, OrderStatus::class);

        $orders = Order::searchBySingleQuery($ordersQuery);

        $orderStats = $this->getEntityStats($orders, OrderStatus::class);
        $orderStatsByStatus = $orderStats['default']['entities_by_status'];
        $orders_per_interval = $orderStats['all']['total_entities'];
        $orders_year_before = $orderStats['all']['year_before'];
        $orders_current_year = $quoteStats['all']['current_year'];
        $orders_intra_company_current_year = $orderStats['all']['intra_company_current_year'];
        $orders_intra_company_year_before = $orderStats['all']['intra_company_year_before'];

        $draftOrderCount = $orderStatsByStatus[OrderStatus::draft()->getName()]['doc_count'];
        $draftOrderPrice = $orderStatsByStatus[OrderStatus::draft()->getName()]['monetary_value'];
        $draftOrderVat = $orderStatsByStatus[OrderStatus::draft()->getName()]['vat_value'];

        $activeOrderCount = $orderStatsByStatus[OrderStatus::active()->getName()]['doc_count'];
        $activeOrderPrice = $orderStatsByStatus[OrderStatus::active()->getName()]['monetary_value'];
        $activeOrderVat = $orderStatsByStatus[OrderStatus::active()->getName()]['vat_value'];

        $deliveredOrderCount = $orderStatsByStatus[OrderStatus::delivered()->getName()]['doc_count'] +
        $orderStatsByStatus[OrderStatus::invoiced()->getName()]['doc_count'];
        $deliveredOrderPrice = $orderStatsByStatus[OrderStatus::delivered()->getName()]['monetary_value'] +
          $orderStatsByStatus[OrderStatus::invoiced()->getName()]['monetary_value'];
        $deliveredOrderVat = $orderStatsByStatus[OrderStatus::delivered()->getName()]['vat_value'] +
          $orderStatsByStatus[OrderStatus::invoiced()->getName()]['vat_value'];

        /** all purchase orders */
        $terms = [PurchaseOrderStatus::draft()->getIndex(), PurchaseOrderStatus::billed()->getIndex(), PurchaseOrderStatus::paid()->getIndex()];
        $noDrafts = [
          PurchaseOrderStatus::submitted()->getIndex(), PurchaseOrderStatus::completed()->getIndex(),
          PurchaseOrderStatus::authorised()->getIndex(), PurchaseOrderStatus::billed()->getIndex(), PurchaseOrderStatus::paid()->getIndex(),
          PurchaseOrderStatus::cancelled()->getIndex(), PurchaseOrderStatus::rejected()->getIndex()
        ];
        $mustNotInclude = [PurchaseOrderStatus::cancelled()->getIndex(), PurchaseOrderStatus::rejected()->getIndex()];
        $purchaseQuery = $this->getQuery(PurchaseOrder::class, $array, $terms, $noDrafts, $mustNotInclude, true, PurchaseOrderStatus::class);
        //dd($purchaseQuery);
        $purchases = PurchaseOrder::searchBySingleQuery($purchaseQuery);

        $purchaseStats = $this->getEntityStats($purchases, PurchaseOrderStatus::class);
        $purchaseStatsByStatus = $purchaseStats['default']['entities_by_status'];
        $purchases_per_interval = $purchaseStats['all']['total_entities'];
        $purchases_year_before = $purchaseStats['all']['year_before'];
        $purchases_current_year = $purchaseStats['all']['current_year'];
        $purchases_intra_company_current_year = $purchaseStats['all']['intra_company_current_year'];
        $purchases_intra_company_year_before = $purchaseStats['all']['intra_company_year_before'];

        $draftPoCount = $purchaseStatsByStatus[PurchaseOrderStatus::draft()->getName()]['doc_count'];
        $draftPoPrice = $purchaseStatsByStatus[PurchaseOrderStatus::draft()->getName()]['monetary_value'];
        $draftPoVat = $purchaseStatsByStatus[PurchaseOrderStatus::draft()->getName()]['vat_value'];

        $awaitingPoCount = $purchaseStatsByStatus[PurchaseOrderStatus::billed()->getName()]['doc_count'];
        $awaitingPoPrice = $purchaseStatsByStatus[PurchaseOrderStatus::billed()->getName()]['monetary_value'];
        $awaitingPoVat = $purchaseStatsByStatus[PurchaseOrderStatus::billed()->getName()]['vat_value'];

        $paidPoCount = $purchaseStatsByStatus[PurchaseOrderStatus::paid()->getName()]['doc_count'];
        $paidPoPrice = $purchaseStatsByStatus[PurchaseOrderStatus::paid()->getName()]['monetary_value'];
        $paidPoVat = $purchaseStatsByStatus[PurchaseOrderStatus::paid()->getName()]['vat_value'];

        /** all invoices */
        $terms = [
          InvoiceStatus::draft()->getIndex(), InvoiceStatus::submitted()->getIndex(), InvoiceStatus::paid()->getIndex(), InvoiceStatus::partial_paid()->getIndex(),
          InvoiceStatus::approval()->getIndex(), InvoiceStatus::authorised()->getIndex(), InvoiceStatus::unpaid()->getIndex(), InvoiceStatus::cancelled()->getIndex(), InvoiceStatus::rejected()->getIndex()
        ];
        $noDrafts = [
          InvoiceStatus::submitted()->getIndex(), InvoiceStatus::paid()->getIndex(), InvoiceStatus::partial_paid()->getIndex(), InvoiceStatus::cancelled()->getIndex(),
          InvoiceStatus::approval()->getIndex(), InvoiceStatus::authorised()->getIndex(), InvoiceStatus::unpaid()->getIndex(), InvoiceStatus::rejected()->getIndex()
        ];
        $invoicesQuery = $this->getInvoiceQuery($array, $terms, $noDrafts);
        $invoices = Invoice::searchBySingleQuery($invoicesQuery);

        $invoiceStats = $this->getEntityStats($invoices, InvoiceStatus::class);

        $invoiceStatsByStatus = $invoiceStats['default']['entities_by_status'];
        $invoices_per_interval = $invoiceStats['all']['total_entities'];
        $invoices_year_before = $invoiceStats['all']['year_before'];
        $invoices_current_year = $invoiceStats['all']['current_year'];

        $intraCompanyDocs = $invoices['aggregations']['intra_company'];
        $intraInvoiceCount = $intraCompanyDocs['doc_count'];
        $intraInvoicePrice = $intraCompanyDocs['monetary_value']['value'];
        $intraInvoiceVat = $intraCompanyDocs['vat_value']['value'];

        $invoiceOverdueDocs = $this->getOverduesStats($invoices['aggregations']);

        $draftInvoiceCount = $invoiceStatsByStatus[InvoiceStatus::draft()->getName()]['doc_count'];
        $draftInvoicePrice = $invoiceStatsByStatus[InvoiceStatus::draft()->getName()]['monetary_value'];
        $draftInvoiceVat = $invoiceStatsByStatus[InvoiceStatus::draft()->getName()]['vat_value'];

        $awaitingInvoiceCount = $invoiceStatsByStatus[InvoiceStatus::submitted()->getName()]['doc_count'] +
          $invoiceStatsByStatus[InvoiceStatus::partial_paid()->getName()]['doc_count'];
        $awaitingInvoicePrice = $invoiceStatsByStatus[InvoiceStatus::submitted()->getName()]['monetary_value'] +
          $invoiceStatsByStatus[InvoiceStatus::partial_paid()->getName()]['monetary_value'];
        $awaitingInvoiceVat = $invoiceStatsByStatus[InvoiceStatus::submitted()->getName()]['vat_value'] +
          $invoiceStatsByStatus[InvoiceStatus::partial_paid()->getName()]['vat_value'];

        $paidInvoiceCount = $invoiceStatsByStatus[InvoiceStatus::paid()->getName()]['doc_count'];
        $paidInvoicePrice = $invoiceStatsByStatus[InvoiceStatus::paid()->getName()]['monetary_value'];
        $paidInvoiceVat = $invoiceStatsByStatus[InvoiceStatus::paid()->getName()]['vat_value'];

        $approvedInvoiceCount = $invoiceStatsByStatus[InvoiceStatus::authorised()->getName()]['doc_count'] +
          $invoiceStatsByStatus[InvoiceStatus::approval()->getName()]['doc_count'];
        $approvedInvoicePrice = $invoiceStatsByStatus[InvoiceStatus::authorised()->getName()]['monetary_value'] +
          $invoiceStatsByStatus[InvoiceStatus::approval()->getName()]['monetary_value'];
        $approvedInvoiceVat = $invoiceStatsByStatus[InvoiceStatus::authorised()->getName()]['vat_value'] +
          $invoiceStatsByStatus[InvoiceStatus::approval()->getName()]['monetary_value'];

        /** earnouts */
        if (!$company->acquisition_date || $company->earnout_years <= 0) {
            $data = [];
        } else {
            $startDate = strtotime($company->acquisition_date);
            $earnoutYears = $company->earnout_years;
            $endDate = strtotime('+' . $earnoutYears . ' years', $startDate);
            $earnoutsQuery = $this->getOrdersForEarnoutQuery($startDate, $endDate);
            $earnouts = Invoice::searchBySingleQuery($earnoutsQuery);

            $orderIdsByInterval = $earnouts['aggregations']['total_earnouts']['buckets'][0]['earnouts_per']['buckets'];
            $data = $this->getEarnoutSeries($orderIdsByInterval, $company);
            array_shift($data);
        }
        //dd($quotes);
        $resultArray = [
          'quotes' => [
              'chosen_period' => [
                  'total_quotes' => [
                      'count' => $quotes['aggregations']['all']['total_entities']['doc_count'] ?? 0,
                      'monetary_value' => round($quotes['aggregations']['all']['total_entities']['monetary_value']['value'] ?? 0, 2),
                      'vat_value' => round($quotes['aggregations']['all']['total_entities']['vat_value']['value'] ?? 0, 2),
                  ],
                  'drafts' => [
                      'count' => $draftQuoteCount,
                      'monetary_value' => round($draftQuotePrice, 2),
                      'vat_value' => round($draftQuoteVat, 2)
                  ],
                  'awaiting_approval' => [
                      'count' => $awaitingQuoteCount,
                      'monetary_value' => round($awaitingQuotePrice, 2),
                      'vat_value' => round($awaitingQuoteVat, 2)
                  ],
                  'declined' => [
                      'count' => $declinedQuoteCount,
                      'monetary_value' => round($declinedQuotePrice, 2),
                      'vat_value' => round($declinedQuoteVat, 2)
                  ],
                  'data' => $this->getSeries($quotes_current_year, $quotes_intra_company_current_year, $interval, $week, true)

              ],
              'year_before' => $this->getSeries($quotes_year_before, $quotes_intra_company_year_before, $interval, $week, true, true)
          ],
          'orders' => [
              'chosen_period' => [
                  'total_orders' => [
                      'count' => $orders['aggregations']['all']['total_entities']['doc_count'] ?? 0,
                      'monetary_value' => round($orders['aggregations']['all']['total_entities']['monetary_value']['value'] ?? 0, 2),
                      'vat_value' => round($orders['aggregations']['all']['total_entities']['vat_value']['value'] ?? 0, 2),
                  ],
                  'drafts' => [
                      'count' => $draftOrderCount,
                      'monetary_value' => round($draftOrderPrice, 2),
                      'vat_value' => round($draftOrderVat, 2)
                  ],
                  'active' => [
                      'count' => $activeOrderCount,
                      'monetary_value' => round($activeOrderPrice, 2),
                      'vat_value' => round($activeOrderVat, 2)
                  ],
                  'delivered' => [
                      'count' => $deliveredOrderCount,
                      'monetary_value' => round($deliveredOrderPrice, 2),
                      'vat_value' => round($deliveredOrderVat, 2)
                  ],
                  'data' => $this->getSeries($orders_current_year, $orders_intra_company_current_year, $interval, $week, true)
              ],
              'year_before' => $this->getSeries($orders_year_before, $orders_intra_company_year_before, $interval, $week, true, true)
          ],
          'purchase_orders' => [
              'chosen_period' => [
                  'total_purchase_orders' => [
                      'count' => $purchases['aggregations']['all']['total_entities']['doc_count'] ?? 0,
                      'monetary_value' => round($purchases['aggregations']['all']['total_entities']['monetary_value']['value'] ?? 0, 2),
                      'vat_value' => round($purchases['aggregations']['all']['total_entities']['vat_value']['value'] ?? 0, 2),
                  ],
                  'drafts' => [
                      'count' => $draftPoCount,
                      'monetary_value' => round($draftPoPrice, 2),
                      'vat_value' => round($draftPoVat, 2)
                  ],
                  'awaiting_payment' => [
                      'count' => $awaitingPoCount,
                      'monetary_value' => round($awaitingPoPrice, 2),
                      'vat_value' => round($awaitingPoVat, 2)
                  ],
                  'paid' => [
                      'count' => $paidPoCount,
                      'monetary_value' => round($paidPoPrice, 2),
                      'vat_value' => round($paidPoVat, 2)
                  ],
                  'data' => [
                      (object)[
                          'name' => 'purchase_orders',
                          'series' => $this->getSeries($purchases_current_year, $purchases_intra_company_current_year, $interval, $week)
                      ]
                  ]
              ],
              'year_before' => [
                  (object)[
                      'name' => 'purchase_orders_prev',
                      'series' => $this->getSeries($purchases_year_before, $purchases_intra_company_year_before, $interval, $week)
                  ]
              ]
          ],
          'invoices' => [
              'chosen_period' => [
                  'total_invoices' => [
                      'count' => $invoices['aggregations']['all']['total_entities']['doc_count'] ?? 0,
                      'monetary_value' => round($invoices['aggregations']['all']['total_entities']['monetary_value']['value'] ?? 0, 2),
                      'vat_value' => round($invoices['aggregations']['all']['total_entities']['vat_value']['value'] ?? 0, 2),
                  ],
                  'drafts' => [
                      'count' => $draftInvoiceCount,
                      'monetary_value' => round($draftInvoicePrice, 2),
                      'vat_value' => round($draftInvoiceVat, 2)
                  ],
                  'awaiting_payment' => [
                      'count' => $awaitingInvoiceCount,
                      'monetary_value' => round($awaitingInvoicePrice, 2),
                      'vat_value' => round($awaitingInvoiceVat, 2)
                  ],
                  'paid' => [
                      'count' => $paidInvoiceCount,
                      'monetary_value' => round($paidInvoicePrice, 2),
                      'vat_value' => round($paidInvoiceVat, 2)
                  ],
                  'approved_authorised' => [
                      'count' => $approvedInvoiceCount,
                      'monetary_value' => round($approvedInvoicePrice, 2),
                      'vat_value' => round($approvedInvoiceVat, 2)
                  ],
                  'overdue' => [
                      'count' => $invoiceOverdueDocs['doc_count'],
                      'vat_value' => $invoiceOverdueDocs['vat_value'],
                      'monetary_value' => $invoiceOverdueDocs['monetary_value']
                  ],
                  'intra_company' => [
                      'count' => $intraInvoiceCount,
                      'vat_value' => $intraInvoiceVat,
                      'monetary_value' => $intraInvoicePrice
                  ],
                  'data' => $this->getInvoiceSeries($invoices_current_year, $interval, $week, true)
              ],
              'year_before' => $this->getInvoiceSeries($invoices_year_before, $interval, $week, true, true)
          ],
          'earnouts' => [
              'chosen_period' => [
                  'data' => $data,
              ]
          ]
        ];

        return $resultArray;
    }

    public function summary($entity, $day, $week, $month, $quarter, $year, $periods)
    {
        $resultPeriod = [];
        $resultArray = [];

        $array = $this->getStartAndEnd($day, $week, $month, $quarter, $year);
        switch ($entity) {
            case 'quotes':
                $class = Quote::class;
                break;
            case 'orders':
                $class = Order::class;
                break;
            case 'invoices':
                $class = Invoice::class;
                break;
            case 'purchase_orders':
                $class = PurchaseOrder::class;
                break;
        }
        $start = $array['start'];
        $end = $array['end'];

        $results1 = $this->getResults($entity, $array['start'], $array['end'], $class);
        $results[1] = collect($results1)->flatten(1);
        $years[1] = $year;

        if ($periods > 0) {
            $results2 = $this->getResults($entity, $array['before_start'], $array['before_end'], $class);
            $results[2] = collect($results2)->flatten(1);
            $years[2] = $year - 1;
        }

        if ($periods > 1) {
            $array = $this->getStartAndEnd($day, $week, $month, $quarter, $year - 2);
            $results3 = $this->getResults($entity, $array['start'], $array['end'], $class);
            $results[3] = collect($results3)->flatten(1);
            $years[3] = $year - 2;
        }
        if ($periods > 2) {
            $results4 = $this->getResults($entity, $array['before_start'], $array['before_end'], $class);
            $results[4] = collect($results4)->flatten(1);
            $years[4] = $year - 3;
        }
        if ($periods > 3) {
            $array = $this->getStartAndEnd($day, $week, $month, $quarter, $year - 4);
            $results5 = $this->getResults($entity, $array['start'], $array['end'], $class);
            $results[5] = collect($results5)->flatten(1);
            $years[5] = $year - 4;
        }

        foreach (Company::all() as $company) {
            $resultArray2 = [];
            $environment = app(Environment::class);
            $environment->setTenant($company);
            $companyArray = [
            'name' => $company->name,
            'id' => $company->id,
            ];
            for ($x = 1; $x <= $periods + 1; $x++) {
                $companyItem = $results[$x]->where('company_name', $company->name)->first();
                $companyArray[$years[$x]] = empty($companyItem) ? 0.0 : $companyItem['monetary_value'];
            }

            $customers = $this->getCustomers($class, $start, $end);
            $companyCustomers = Customer::whereIn('id', $customers)->get();
            if ($entity == 'purchase_orders') {
                $po_without_orders = new Customer();
                $po_without_orders->id = 'General purchase orders';
                $po_without_orders->name = 'General purchase orders';
                $companyCustomers->push($po_without_orders);
            }

            foreach ($companyCustomers as $customer) {
                $resultArray3 = [];
                $customerArray = [
                'name' => $customer->name,
                'id' => $customer->id
                ];
                for ($y = 1; $y <= $periods + 1; $y++) {
                    $companyItem2 = $results[$y]->where('company_name', $company->name)->first();
                    $companyItem2 = collect($companyItem2)->flatten(1);
                    $item = $companyItem2->where('customer_id', $customer->id)->first();
                    $customerArray[$years[$y]] = empty($item) ? 0.0 : $item['monetary_value'];
                    if (!empty($item) && count($item[$entity]) > 0) {
                        foreach ($item[$entity] as $entityItem) {
                            $itemArray = [
                            'name' => $entityItem['number'],
                            'id' => $entityItem['id'],
                            $years[$y] => (float)$entityItem['price']
                            ];
                            array_push($resultArray3, $itemArray);
                        }
                    }
                }
                $customerArray['items'] = $resultArray3;
                array_push($resultArray2, $customerArray);
                $companyArray['customers'] = $resultArray2;
            }
            array_push($resultArray, $companyArray);
        }

        for ($x = 1; $x <= $periods + 1; $x++) {
            $resultData[(int)$years[$x]] = $results[$x][0];

            $array = [
            'key' => (int)$years[$x],
            'name' => (string)$years[$x]
            ];
            array_push($resultPeriod, $array);
        }
        $resultData['companies'] = $resultArray;
        $result = [
          'data' => $resultData,
          'periods' => $resultPeriod
        ];

        return $result;
    }

    public function summaryCompany($entity, $day, $week, $month, $quarter, $year, $periods, $company_id)
    {
        $resultPeriod = [];

        $array = $this->getStartAndEnd($day, $week, $month, $quarter, $year);
        switch ($entity) {
            case 'quotes':
                $class = Quote::class;
                break;
            case 'orders':
                $class = Order::class;
                break;
            case 'invoices':
                $class = Invoice::class;
                break;
            case 'purchase_orders':
                $class = PurchaseOrder::class;
                break;
        }

        $customers = $this->getCustomers($class, $array['start'], $array['end']);

        $companyCustomers = Customer::whereIn('id', $customers)->get();
        if ($entity == 'purchase_orders') {
            $po_without_orders = new Customer();
            $po_without_orders->id = 'General purchase orders';
            $po_without_orders->name = 'General purchase orders';
            $companyCustomers->push($po_without_orders);
        }

        $results1 = $this->getCompanyResults($entity, $array['start'], $array['end'], $class);
        $results[1] = collect($results1)->flatten(1);
        $years[1] = $year;

        if ($periods > 0) {
            $results2 = $this->getCompanyResults($entity, $array['before_start'], $array['before_end'], $class);
            $results[2] = collect($results2)->flatten(1);
            $years[2] = $year - 1;
        }
        if ($periods > 1) {
            $array = $this->getStartAndEnd($day, $week, $month, $quarter, $year - 2);
            $results3 = $this->getCompanyResults($entity, $array['start'], $array['end'], $class);
            $results[3] = collect($results3)->flatten(1);
            $years[3] = $year - 2;
        }
        if ($periods > 2) {
            $results4 = $this->getCompanyResults($entity, $array['before_start'], $array['before_end'], $class);
            $results[4] = collect($results4)->flatten(1);
            $years[4] = $year - 3;
        }
        if ($periods > 3) {
            $array = $this->getStartAndEnd($day, $week, $month, $quarter, $year - 4);
            $results5 = $this->getCompanyResults($entity, $array['start'], $array['end'], $class);
            $results[5] = collect($results5)->flatten(1);
            $years[5] = $year - 4;
        }

        $resultArray2 = [];
        $companyArray = [];
        $company = Company::findOrFail($company_id);
        array_push($companyArray, [
          'name' => $company->name,
          'id' => $company->id,
        ]);
        for ($x = 1; $x <= $periods + 1; $x++) {
            $companyArray[0][$years[$x]] = $results[$x][0];
        }

        foreach ($companyCustomers as $customer) {
            $resultArray3 = [];
            $customerArray = [
            'name' => $customer->name,
            'id' => $customer->id
            ];
            for ($x = 1; $x <= $periods + 1; $x++) {
                $item = $results[$x]->where('customer_id', $customer->id)->first();
                $customerArray[$years[$x]] = empty($item) ? 0.0 : $item['monetary_value'];
                if (!empty($item) && count($item[$entity]) > 0) {
                    foreach ($item[$entity] as $entityItem) {
                        $itemArray = [
                        'name' => $entityItem['number'],
                        'id' => $entityItem['id'],
                        $years[$x] => (float)$entityItem['price']
                        ];
                        array_push($resultArray3, $itemArray);
                    }
                }
            }
            $customerArray['items'] = $resultArray3;
            array_push($resultArray2, $customerArray);
            $companyArray[0]['customers'] = $resultArray2;
        }

        for ($x = 1; $x <= $periods + 1; $x++) {
            $resultData[(int)$years[$x]] = $results[$x][0];

            $array = [
            'key' => (int)$years[$x],
            'name' => (string)$years[$x]
            ];
            array_push($resultPeriod, $array);
        }
        $resultData['companies'] = $companyArray;
        $result = [
          'data' => $resultData,
          'periods' => $resultPeriod
        ];

        return $result;
    }

    public function getStartAndEnd($day, $week, $month, $quarter, $year)
    {
        $start = $end = $interval = $format = $min = $max = $bounds = $before_start = $before_end = $before_min = $before_max = '';
        $weekBool = false;

        if ($year == 0) {
            $start = strtotime('01/01/' . now()->year);
            $end = strtotime('+1 year', $start);
            $interval = 'month';
            $format = 'yyyy-MM';
            $min = now()->year . '-01';
            $max = now()->year . '-12';
            $before_start = strtotime('-1 year', $start);
            $before_end = strtotime('-1 year', $end);
            $before_min = now()->year - 1 . '-01';
            $before_max = now()->year - 1 . '-12';
        } elseif ($quarter != 0) {
            switch ($quarter) {
                case 1:
                    $start = strtotime('01/01/' . $year);
                    $end = strtotime('04/01/' . $year);
                    $min = $year . '-01';
                    $max = $year . '-03';
                    $before_start = strtotime('-1 year', $start);
                    $before_end = strtotime('-1 year', $end);
                    $before_min = $year - 1 . '-01';
                    $before_max = $year - 1 . '-03';
                    break;
                case 2:
                    $start = strtotime('04/01/' . $year);
                    $end = strtotime('07/01/' . $year);
                    $min = $year . '-04';
                    $max = $year . '-06';
                    $before_start = strtotime('-1 year', $start);
                    $before_end = strtotime('-1 year', $end);
                    $before_min = $year - 1 . '-04';
                    $before_max = $year - 1 . '-06';
                    break;
                case 3:
                    $start = strtotime('07/01/' . $year);
                    $end = strtotime('10/01/' . $year);
                    $min = $year . '-07';
                    $max = $year . '-09';
                    $before_start = strtotime('-1 year', $start);
                    $before_end = strtotime('-1 year', $end);
                    $before_min = $year - 1 . '-07';
                    $before_max = $year - 1 . '-09';
                    break;
                case 4:
                    $start = strtotime('10/01/' . $year);
                    $end = strtotime('01/01/' . ($year + 1));
                    $min = $year . '-10';
                    $max = $year . '-12';
                    $before_start = strtotime('-1 year', $start);
                    $before_end = strtotime('-1 year', $end);
                    $before_min = $year - 1 . '-10';
                    $before_max = $year - 1 . '-12';
                    break;
            }
            $interval = 'month';
            $format = 'yyyy-MM';
        } elseif ($day != 0) {
            $start = strtotime($month . '/' . $day . '/' . $year);
            $end = strtotime('+1 day', $start);
            $bounds = strtotime('-1 minute', $end);
            $interval = 'hour';
            $format = 'yyyy-MM-dd HH';
            $min = date('Y-m-d', substr($start, 0, 10)) . ' 00';
            $max =  date('Y-m-d', substr($bounds, 0, 10)) . ' 23';
            $before_start = strtotime('-1 year', $start);
            $before_end = strtotime('-1 year', $end);
            $before_min = date('Y-m-d', substr($before_start, 0, 10)) . ' 00';
            $before_max =  date('Y-m-d', substr(strtotime('-1 minute', $before_end), 0, 10)) . ' 23';
        } elseif ($month != 0) {
            $start = strtotime($month . '/01/' . $year);
            $end = strtotime('+1 month', $start);
            $bounds = strtotime('-1 hour', $end);
            $interval = 'day';
            $format = 'yyyy-MM-dd';
            $min = date('Y-m-d', substr($start, 0, 10));
            $max =  date('Y-m-d', substr($bounds, 0, 10));
            $before_start = strtotime('-1 year', $start);
            $before_end = strtotime('-1 year', $end);
            $before_min = date('Y-m-d', substr($before_start, 0, 10));
            $before_max =  date('Y-m-d', substr(strtotime('-1 hour', $before_end), 0, 10));
        } elseif ($week != 0) {
            $new_date = new \DateTime('today');
            $new_date->setISODate($year, $week);
            $new_date = (new Carbon($new_date))->isoFormat('MM/DD/YYYY');
            $start = strtotime($new_date);
            $end = strtotime('+1 week', $start);
            $bounds = strtotime('-1 hour', $end);
            $interval = 'day';
            $format = 'yyyy-MM-dd';
            $min = date('Y-m-d', substr($start, 0, 10));
            $max =  date('Y-m-d', substr($bounds, 0, 10));
            $weekBool = true;

            $second_date = new \DateTime('today');
            $second_date->setISODate($year - 1, $week);
            $second_date = (new Carbon($second_date))->isoFormat('MM/DD/YYYY');
            $before_start = strtotime($second_date);
            $before_end = strtotime('+1 week', $before_start);
            $before_min = date('Y-m-d', substr($before_start, 0, 10));
            $before_max =  date('Y-m-d', substr(strtotime('-1 hour', $before_end), 0, 10));
        } else {
            $start = strtotime('01/01/' . $year);
            $end = strtotime('+1 year', $start);
            $interval = 'month';
            $format = 'yyyy-MM';
            $min = $year . '-01';
            $max = $year . '-12';
            $before_start = strtotime('-1 year', $start);
            $before_end = strtotime('-1 year', $end);
            $before_min = $year - 1 . '-01';
            $before_max = $year - 1 . '-12';
        }

        return [
          'start' => $start,
          'end' => $end,
          'interval' => $interval,
          'format' => $format,
          'min' => $min,
          'max' => $max,
          'before_start' => $before_start,
          'before_end' => $before_end,
          'before_min' => $before_min,
          'before_max' => $before_max,
          'week' => $weekBool
        ];
    }

    /**
     * Exclude intra companies subjects from the query if intraCompanyOnly parameter is true
     * @param array $query
     * @param boolean $intraCompanyOnly
     * @return array
     */
    private function addIntraCompanyQuery(array $query, $intraCompanyOnly = null)
    {
        if ($intraCompanyOnly !== null) {
            $query[] = ['terms' => ['intra_company' =>[$intraCompanyOnly]]];
        }
        return $query;
    }

    private function getTotalEntitiesQuery($array, $terms, $noDrafts)
    {
        $interval = $array['interval'];
        $format = $array['format'];
        $min = $array['min'];
        $max = $array['max'];


        extract(getFormatedFieldsWithUserCurrency());

        return [
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
          'entities_per' => [
              'date_histogram' => [
                  'field' => 'date',
                  'interval' => $interval,
                  'min_doc_count' => 0,
                  'extended_bounds' => [
                      'min' => $min,
                      'max' => $max
                  ],
                  'format' => $format,
              ],
              'aggs' => [
                  'without_drafts' => [
                      'filter' => ['terms' => ['status' => $noDrafts]],
                      'aggs' => [
                          'intra_company' =>[
                              'filter' => ['terms' => ['intra_company' => [true]]],
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
                                  'project' => [
                                      'nested' => [
                                          'path' => 'project_info'
                                      ],
                                      'aggs' => [
                                          'po_cost' => [
                                              'terms' => ['field' => $project_costs],
                                              'aggs' => [
                                                  'total' => [
                                                      'sum' => ['field' => $project_costs]
                                                  ]
                                              ]
                                          ],
                                          'sum_po_cost' => [
                                              'sum_bucket' => [
                                                  'buckets_path' => 'po_cost>total'
                                              ]
                                          ],
                                          'po_vat' => [
                                              'terms' => ['field' => $project_vat],
                                              'aggs' => [
                                                  'total' => [
                                                      'sum' => ['field' => $project_vat]
                                                  ]
                                              ]
                                          ],
                                          'sum_po_vat' => [
                                              'sum_bucket' => [
                                                  'buckets_path' => 'po_vat>total'
                                              ]
                                          ],
                                          'employee_cost' => [
                                              'terms' => ['field' => $project_employee],
                                              'aggs' => [
                                                  'total' => [
                                                      'sum' => ['field' => $project_employee]
                                                  ]
                                              ]
                                          ],
                                          'sum_employee_cost' => [
                                              'sum_bucket' => [
                                                  'buckets_path' => 'employee_cost>total'
                                              ]
                                          ],
                                      ]
                                  ]
                              ]
                          ],
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
                          'project' => [
                              'nested' => [
                                  'path' => 'project_info'
                              ],
                              'aggs' => [
                                  'po_cost' => [
                                      'terms' => ['field' => $project_costs],
                                      'aggs' => [
                                          'total' => [
                                              'sum' => ['field' => $project_costs]
                                          ]
                                      ]
                                  ],
                                  'sum_po_cost' => [
                                      'sum_bucket' => [
                                          'buckets_path' => 'po_cost>total'
                                      ]
                                  ],
                                  'po_vat' => [
                                      'terms' => ['field' => $project_vat],
                                      'aggs' => [
                                          'total' => [
                                              'sum' => ['field' => $project_vat]
                                          ]
                                      ]
                                  ],
                                  'sum_po_vat' => [
                                      'sum_bucket' => [
                                          'buckets_path' => 'po_vat>total'
                                      ]
                                  ],
                                  'employee_cost' => [
                                      'terms' => ['field' => $project_employee],
                                      'aggs' => [
                                          'total' => [
                                              'sum' => ['field' => $project_employee]
                                          ]
                                      ]
                                  ],
                                  'sum_employee_cost' => [
                                      'sum_bucket' => [
                                          'buckets_path' => 'employee_cost>total'
                                      ]
                                  ],
                              ]
                          ]
                      ]
                  ],
              ],
          ],
          'term_entities' => [
              'terms' => [
                  'field' => 'status',
                  'include' => $terms,
                  'min_doc_count' => 0,
                  'order' => [
                      '_key' => 'asc'
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
              ]
          ]
        ];
    }



    private function getQuery($entity, $array, $terms, $noDrafts, $exclude, $isPurchaseOrder = false, $statusClass = null)
    {
        $start = $array['start'];
        $end = $array['end'];
        $interval = $array['interval'];
        $format = $array['format'];
        $min = $array['min'];
        $max = $array['max'];
        $before_start = $array['before_start'];
        $before_end = $array['before_end'];
        $before_min = $array['before_min'];
        $before_max = $array['before_max'];
        $purchaseOrderQuery = [];

        if ($isPurchaseOrder) {
            array_push($purchaseOrderQuery, ['match' => ['is_contractor' => false]]);
        }

        extract(getFormatedFieldsWithUserCurrency());

        $query = [
          'must'=>[
              [
                  'range' => [
                      'date' =>  [
                          'gte' => $start,
                          'lte' => $end,
                      ]
                  ],
              ],
              [
                  'terms' => [
                      'intra_company' =>  [false]
                  ],
              ]
          ]
        ];

        $customQuery = [
          'must_not'=> [
              [
                  'terms' => ['status' => [$this->getEntityCancelledIndex($entity)]]
              ]
          ],
          'must' => $purchaseOrderQuery
        ];

        $shadowQuery = [];

        if ($statusClass != OrderStatus::class) {
            $shadowQuery = [
              ['match' => ['shadow' => true]]
            ];

            $customQuery['must_not'][] = $shadowQuery;
        }

        return [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => $purchaseOrderQuery,
                  'must_not' => $shadowQuery
              ]
          ],
          'aggs' => [
              'all' => [
                  'filter'=>[
                      'bool'=> [
                          'must'=>[
                              [
                                  'range' => [
                                      'date' =>  [
                                          'gte' => $start,
                                          'lte' => $end,
                                      ]
                                  ],
                              ],
                          ],
                          'must_not'=> [
                              [
                                  'terms' => [
                                      'status' =>  [$this->getEntityDraftIndex($entity)]
                                  ],
                              ]
                          ]
                      ]
                  ],
                  'aggs'=> [
                      'total_entities'=> [
                          'filter'=>[
                              'bool'=>$query
                          ],
                          'aggs'=>$this->getTotalEntitiesQuery($array, $terms, $noDrafts)
                      ],

                      'intra_company_current_year' => [
                          'filter'=>[
                              'bool'=> [
                                  'must'=>[
                                      [
                                          'range' => [
                                              'date' =>  [
                                                  'gte' => $start,
                                                  'lte' => $end,
                                              ]
                                          ],
                                      ],
                                      [
                                          'terms' => [
                                              'intra_company' =>  [true]
                                          ],
                                      ]
                                  ]
                              ]
                          ],
                          'aggs' => $this->getAnalyticSeriesSubQuery($array, $noDrafts, false)
                      ],
                      'current_year' => [
                          'filter'=>[
                              'bool'=> [
                                  'must'=>[
                                      [
                                          'range' => [
                                              'date' =>  [
                                                  'gte' => $start,
                                                  'lte' => $end,
                                              ]
                                          ],
                                      ],
                                      [
                                          'terms' => [
                                              'intra_company' =>  [false]
                                          ],
                                      ]
                                  ]
                              ]
                          ],
                          'aggs' => $this->getAnalyticSeriesSubQuery($array, $noDrafts, false)
                      ],
                      'intra_company_year_before' => [
                          'filter'=>[
                              'bool'=> [
                                  'must'=>[
                                      [
                                          'range' => [
                                              'date' =>  [
                                                  'gte' => $before_start,
                                                  'lte' => $before_end,
                                              ]
                                          ],
                                      ],
                                      [
                                          'terms' => [
                                              'intra_company' =>  [true]
                                          ],
                                      ]
                                  ]
                              ]
                          ],
                          'aggs' => $this->getAnalyticSeriesSubQuery($array, $noDrafts, true)
                      ],
                      'year_before' => [
                          'filter'=>[
                              'bool'=> [
                                  'must'=>[
                                      [
                                          'range' => [
                                              'date' =>  [
                                                  'gte' => $before_start,
                                                  'lte' => $before_end,
                                              ]
                                          ],
                                      ],
                                      [
                                          'terms' => [
                                              'intra_company' =>  [false]
                                          ],
                                      ]
                                  ]
                              ]
                          ],
                          'aggs' => $this->getAnalyticSeriesSubQuery($array, $noDrafts, true)
                      ],
                  ]
              ],
              'default' => [
                  'filter'=>[
                      'bool' => $customQuery
                  ],
                  'aggs'=> [
                      'entities_by_status' => [
                          'filters' => $this->buildFilteringsSubQuery($statusClass, $start, $end, false),
                          'aggs'=> $this->getTotalEntitiesQuery($array, $terms, $noDrafts)
                      ],
                      'intra_company_entities_by_status' => [
                          'filters' => $this->buildFilteringsSubQuery($statusClass, $start, $end, true),
                          'aggs'=> $this->getTotalEntitiesQuery($array, $terms, $noDrafts)
                      ],
                      'normal_total_entities' => [
                          'date_range' => [
                              'field' => 'date',
                              'ranges' => [
                                  ['from' => $start, 'to' => $end],
                              ],
                          ],
                          'aggs'=> $this->getTotalEntitiesQuery($array, $terms, $noDrafts)
                      ],
                      'total_entities'=> [
                          'filter'=>[
                              'bool'=>[
                                  'must'=>[
                                      [
                                          'range' => [
                                              'date' =>  [
                                                  'gte' => $start,
                                                  'lte' => $end,
                                              ]
                                          ],
                                      ],
                                  ]
                              ]
                          ],
                          'aggs'=>$this->getTotalEntitiesQuery($array, $terms, $noDrafts)
                      ],
                  ]
              ],
          ]
        ];
    }

    private function getAnalyticSeriesSubQuery(array $array, $noDrafts, bool $before = true)
    {
        $interval = $array['interval'];
        $format = $array['format'];

        if ($before) {
            $min = $array['before_min'];
            $max = $array['before_max'];
        } else {
            $min = $array['min'];
            $max = $array['max'];
        }

        extract(getFormatedFieldsWithUserCurrency());
        return [
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
          'entities_per' => [
              'date_histogram' => [
                  'field' => 'date',
                  'interval' => $interval,
                  'min_doc_count' => 0,
                  'extended_bounds' => [
                      'min' => $min,
                      'max' => $max
                  ],
                  'format' => $format,
              ],
              'aggs' => [
                  'without_drafts' => [
                      'filter' => ['terms' => ['status' => $noDrafts]],
                      'aggs' => [
                          'intra_company' =>[
                              'filter' => ['terms' => ['intra_company' => [true]]],
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
                                  'project' => [
                                      'nested' => [
                                          'path' => 'project_info'
                                      ],
                                      'aggs' => [
                                          'po_cost' => [
                                              'terms' => ['field' => $project_costs],
                                              'aggs' => [
                                                  'total' => [
                                                      'sum' => ['field' => $project_costs]
                                                  ]
                                              ]
                                          ],
                                          'sum_po_cost' => [
                                              'sum_bucket' => [
                                                  'buckets_path' => 'po_cost>total'
                                              ]
                                          ],
                                          'po_vat' => [
                                              'terms' => ['field' => $project_vat],
                                              'aggs' => [
                                                  'total' => [
                                                      'sum' => ['field' => $project_vat]
                                                  ]
                                              ]
                                          ],
                                          'sum_po_vat' => [
                                              'sum_bucket' => [
                                                  'buckets_path' => 'po_vat>total'
                                              ]
                                          ],
                                          'employee_cost' => [
                                              'terms' => ['field' => $project_employee],
                                              'aggs' => [
                                                  'total' => [
                                                      'sum' => ['field' => $project_employee]
                                                  ]
                                              ]
                                          ],
                                          'sum_employee_cost' => [
                                              'sum_bucket' => [
                                                  'buckets_path' => 'employee_cost>total'
                                              ]
                                          ],
                                      ]
                                  ]
                              ]
                          ],
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
                          'project' => [
                              'nested' => [
                                  'path' => 'project_info'
                              ],
                              'aggs' => [
                                  'po_cost' => [
                                      'terms' => ['field' => $project_costs],
                                      'aggs' => [
                                          'total' => [
                                              'sum' => ['field' => $project_costs]
                                          ]
                                      ]
                                  ],
                                  'sum_po_cost' => [
                                      'sum_bucket' => [
                                          'buckets_path' => 'po_cost>total'
                                      ]
                                  ],
                                  'po_vat' => [
                                      'terms' => ['field' => $project_vat],
                                      'aggs' => [
                                          'total' => [
                                              'sum' => ['field' => $project_vat]
                                          ]
                                      ]
                                  ],
                                  'sum_po_vat' => [
                                      'sum_bucket' => [
                                          'buckets_path' => 'po_vat>total'
                                      ]
                                  ],
                                  'employee_cost' => [
                                      'terms' => ['field' => $project_employee],
                                      'aggs' => [
                                          'total' => [
                                              'sum' => ['field' => $project_employee]
                                          ]
                                      ]
                                  ],
                                  'sum_employee_cost' => [
                                      'sum_bucket' => [
                                          'buckets_path' => 'employee_cost>total'
                                      ]
                                  ],
                              ]
                          ]
                      ]
                  ],
              ],
          ],
        ];
    }

    private function enableShadowOnBuildFilteringsSubQuery($statusClass)
    {
        $excludedFromTheShadow = [OrderStatus::class, PurchaseOrderStatus::class];
        if (!in_array($statusClass, $excludedFromTheShadow)) {
            return [['terms' => ['shadow' => [false]]]];
        }
        return [];
    }

    /**
     * Build entity query according to creation date, delivery date and payment date
     * @author douglas
     * @param array $terms
     * @param mixed $statusClass
     * @param string $start
     * @param string $end
     * @return array
     */
    private function buildFilteringsSubQuery($statusClass, string $start, string $end, $intraCompanyOnly = false)
    {
        /**
         * @var array
         */
        $creationDateStatusNames = ['draft','active','partial_paid','authorised','approval','sent'];
        /**
         * @var array
         */
        $deliveringDateStatusNames = ['delivered','invoiced'];
        /**
         * @var array
         */
        $dateStatusNamesLessThan = ['billed'];
        /**
         * @var array
         */
        $paymentDateStatusNames = ['paid'];

        $filteredByCreationDate = [];
        /**
         * @var array
         */
        $filteredByDeliveringDate = [];
        /**
         * @var array
         */
        $filteredByPaymentDate = [];
        /**
         * @var array
         */
        $filteredByDate = [];

        /**
         * @var array
         */
        $filteredByDateLessThan = [];

        foreach ($statusClass::getValues() as $statusName) {
            $statusName = str_replace(' ', '_', strtolower($statusName));
            if (in_array($statusName, $creationDateStatusNames)) {
                $filteredByCreationDate[] = $statusClass::$statusName()->getIndex();
            } elseif (in_array($statusName, $deliveringDateStatusNames)) {
                $filteredByDeliveringDate[] = $statusClass::$statusName()->getIndex();
            } elseif (in_array($statusName, $paymentDateStatusNames)) {
                $filteredByPaymentDate[] = $statusClass::$statusName()->getIndex();
            } elseif (in_array($statusName, $dateStatusNamesLessThan)) {
                $filteredByDateLessThan[] = $statusClass::$statusName()->getIndex();
            }
        }

        $validatedStatuses = array_merge($filteredByCreationDate, $filteredByDeliveringDate, $filteredByPaymentDate);
        $filteredByDate = array_diff($statusClass::getIndices(), $validatedStatuses);
        $filteredByDate = array_values($filteredByDate);

        $query = ['filters' => [
          'by_date' => [
              'bool' => [
                'should' => [
                    [
                        'bool' => [
                            'must' => $this->addIntraCompanyQuery([
                                ['terms' => ['status' => $filteredByDate]],
                                ['range' => ['date' => ['gte' => $start, 'lte' => $end]]],
                            ], $intraCompanyOnly)
                        ],
                    ],
                    [
                        'bool' => [
                            'must' => $this->addIntraCompanyQuery([
                                ['terms' => ['status' => $filteredByDateLessThan]],
                                ['range' => ['date' => ['lte' => $end]]],
                            ], $intraCompanyOnly)
                        ],
                    ]
                ],
              ]
          ],
          'by_creation_date' => [
              'bool' => [
                  'must' => $this->addIntraCompanyQuery([
                      ['terms' => ['status' => $filteredByCreationDate]],
                      ['range' => ['created_at' => ['gte' => $start, 'lte' => $end]]],
                  ], $intraCompanyOnly)
              ]
          ],
        ]];

        if (!empty($filteredByDeliveringDate)) {
            $query['filters']['by_delivery_date'] = [
              'bool' => [
                  'must' => $this->addIntraCompanyQuery([
                      ['terms' => ['status' => $filteredByDeliveringDate]],
                      ['range' => ['delivered_at' => ['gte' => $start, 'lte' => $end]]],
                  ], $intraCompanyOnly)
              ]
            ];
        }

        if (!empty($filteredByPaymentDate)) {
            $query['filters']['by_payment_date'] = [
              'bool' => [
                  'must' => $this->addIntraCompanyQuery([
                      ['terms' => ['status' => $filteredByPaymentDate]],
                      ['range' => ['pay_date' => ['gte' => $start, 'lte' => $end]]],
                  ], $intraCompanyOnly)
              ]
            ];
        }

        foreach ($query['filters'] as $key => $subQuery) {
            if (!empty($subQuery['bool']['must'])) {
                $query['filters'][$key]['bool']['must'] = array_merge(
                    $this->enableShadowOnBuildFilteringsSubQuery($statusClass),
                    $subQuery['bool']['must']
                );
            } elseif (!empty($subQuery['bool']['should'])) {
                foreach ($subQuery['bool']['should'] as $index => $should) {
                    $query['filters'][$key]['bool']['should'][$index]['bool']['must'] = array_merge(
                        $this->enableShadowOnBuildFilteringsSubQuery($statusClass),
                        $should['bool']['must']
                    );
                }
            }
        }

        return $query;
    }

    private function getSeries($array, $intra_array, $interval, $week, $grossMargin = false, $yearBefore = false)
    {
        $previous = '';
        if ($yearBefore) {
            $previous = ' prev';
        }
        return array_map(function ($r, $ir) use ($interval, $week, $grossMargin, $previous) {
            $r = Arr::only($r, ['key_as_string', 'without_drafts']);
            $ir = Arr::only($ir ?? [], ['key_as_string', 'without_drafts']);
            if ($interval == 'month') {
                $m['name'] = date('F', strtotime($r['key_as_string'])) . $previous;
            } elseif ($interval == 'day') {
                if ($week) {
                    $m['name'] = date('l', strtotime($r['key_as_string'])) . $previous;
                } else {
                    $m['name'] = date('j', strtotime($r['key_as_string'])) . $previous;
                }
            } else {
                $m['name'] = substr($r['key_as_string'], -2) . $previous;
            }

            $m['value'] = round($r['without_drafts']['monetary_value']['value'], 2);
            $m['vat_value'] = round($r['without_drafts']['vat_value']['value'], 2);
            $m['quantity'] = $r['without_drafts']['doc_count'];

            $m['intra_company_value'] = round($ir['without_drafts']['monetary_value']['value'] ?? 0, 2);
            $m['intra_company_vat_value'] = round($ir['without_drafts']['vat_value']['value'] ?? 0, 2);
            $m['intra_company_quantity'] = $ir['without_drafts']['doc_count'] ?? 0;

            if ($grossMargin) {
                $po_costs = $r['without_drafts']['project']['sum_po_cost']['value'];
                $employee_costs = $r['without_drafts']['project']['sum_employee_cost']['value'];
                $s['costs'] = round($po_costs + $employee_costs, 2);
                $s['gross_margin'] = $m['value'] - $s['costs'];

                $intra_company_po_costs = $ir['without_drafts']['project']['sum_po_cost']['value'] ?? 0;
                $intra_company_employee_costs = $ir['without_drafts']['project']['sum_employee_cost']['value'] ?? 0;
                $s['intra_company_costs'] = round($intra_company_po_costs + $intra_company_employee_costs, 2);
                $s['intra_company_gross_margin'] = $m['intra_company_value'] - $s['intra_company_costs'];

                $item = [
                'name' => 'gross_margin',
                'value' => $s['gross_margin'],
                'revenue' => $m['value'],
                'revenue_vat' => $m['vat_value']
                ];
                $item2 = [
                'name' => 'costs',
                'value' => $s['costs'],
                'revenue' => $m['value'],
                'revenue_vat' => $m['vat_value']
                ];

                $item3 = [
                'name' => 'intra_company_gm',
                'value' => $s['intra_company_gross_margin'],
                'revenue' => $m['intra_company_value'],
                'revenue_vat' => $m['intra_company_vat_value']
                ];
                $item4 = [
                'name' => 'intra_company_costs',
                'value' => $s['intra_company_costs'],
                'revenue' => $m['intra_company_value'],
                'revenue_vat' => $m['intra_company_vat_value']
                ];

                unset($m['vat_value']);
                unset($m['value']);
                unset($m['quantity']);
                unset($m['intra_company_vat_value']);
                unset($m['intra_company_value']);
                unset($m['intra_company_quantity']);

                $m['series'] = [];
                array_push($m['series'], $item, $item2, $item3, $item4);
            }

            return $m;
        }, $array, $intra_array);
    }

    private function getInvoicesSerieSalaries($r, $interval, $week, $single = false, $yearBefore = false)
    {

        if (empty($r)) {
            return [0, []];
        }
        $m = [];
        $previous = '';
        if ($yearBefore) {
            $previous = ' prev';
        }

        if ($interval == 'month') {
            $m['name'] = date('F', strtotime($r['key_as_string'])) . $previous;
            $endKey = strtotime('+1 month', $r['key'] / 1000);
        } elseif ($interval == 'day') {
            if ($week) {
                $m['name'] = date('l', strtotime($r['key_as_string'])) . $previous;
            } else {
                $m['name'] = date('j', strtotime($r['key_as_string'])) . $previous;
            }
            $endKey = strtotime('+1 day', $r['key']  / 1000);
        } else {
            $m['name'] = substr($r['key_as_string'], -2) . $previous;
            $endKey = strtotime('+1 hour', $r['key'] / 1000);
        }

      /** employee salary */
        $salaries = $this->employeeQuery($r['key'] / 1000, $endKey, $single);
        $totalSalary = $salaries['aggregations']['salary']['value'];

        if ($interval == 'month') {
            $startedIn = $salaries['aggregations']['started_in_period']['buckets'];
            $endedIn = $salaries['aggregations']['ended_in_period']['buckets'];
            $notFullSalaryStart = [];
            $notFullSalaryEnd = [];
            if ($startedIn[0]['doc_count'] > 0) {
                $notFullSalaryStart = array_map(function ($k) use ($startedIn) {
                    $daysNotWorked = ceil(abs($k['key_as_string'] - $startedIn[0]['from_as_string']) / 86400);
                    $daySalary = $k['salary']['value'] / 30;
                    return $k['salary']['value'] - ($daysNotWorked * $daySalary);
                }, $startedIn[0]['started_on']['buckets']);
            }
            $sumStart = array_sum($notFullSalaryStart);

            if ($endedIn[0]['doc_count'] > 0) {
                $notFullSalaryEnd = array_map(function ($k) use ($endedIn) {
                    $daysNotWorked = ceil(abs($endedIn[0]['to_as_string'] - $k['key_as_string']) / 86400);
                    $daySalary = $k['salary']['value'] / 30;
                    return $k['salary']['value'] - ($daysNotWorked * $daySalary);
                }, $endedIn[0]['ended_on']['buckets']);
            }
            $sumEnd = array_sum($notFullSalaryEnd);

            $salaryCost = $totalSalary - $sumStart - $sumEnd;
        } elseif ($interval == 'day') {
            $salaryCost = $totalSalary / 30;
        } else {
            $salaryCost = ($totalSalary / 30) / 24;
        }

        return [$salaryCost, $m];
    }

    private function getInvoiceSeries($array, $interval, $week, $single = false, $yearBefore = false)
    {
        $x = 0;
        return array_map(function ($r) use ($interval, $week, $single, $yearBefore, &$x) {

            list($salaryCost, $m) = $this->getInvoicesSerieSalaries($r, $interval, $week, $single, $yearBefore);

            $s['quantity'] = $r['outgoing']['without_drafts']['doc_count'];
            $s['value'] = $r['outgoing']['without_drafts']['monetary_value']['value'];
            $s['vat_value'] = $r['outgoing']['without_drafts']['vat_value']['value'];
            $s['costs'] = $r['incoming']['without_drafts']['po_invoices']['monetary_value']['value'];
            $s['gross_margin'] = round($s['value'] - ($s['costs'] + $salaryCost), 2);

            $payableInvoices = $r['incoming']['without_drafts']['monetary_value']['value'];
            $s['net_profit'] = round($s['value'] - ($payableInvoices + $salaryCost), 2);
            $s['gross_margin_percent'] = 0;

            if (!empty($s['value'])) {
                $s['gross_margin_percent'] = round(safeDivide($s['gross_margin'], $s['value']) * 100, 2);
            }

            $item = [
              'name' => 'production_costs',
              'value' => round($s['costs'] + $salaryCost, 2),
              'revenue' => round($s['value'], 2)
            ];
            $item2 = [
              'name' => 'general_costs',
              'value' => round($payableInvoices - $s['costs'], 2),
              'revenue' => round($s['value'], 2),
              'gross_margin' => round($s['gross_margin'], 2),
              'gross_margin_percent' => round($s['gross_margin_percent'], 2),
            ];
            $item3 = [
              'name' => 'net_profit',
              'value' => $s['net_profit'],
              'revenue' => round($s['value'], 2),
              'gross_margin' => round($s['gross_margin'], 2),
              'gross_margin_percent' => round($s['gross_margin_percent'], 2),
            ];

            $m['series'] = [];
            array_push($m['series'], $item, $item2, $item3);

            $x++;
            return $m;
        }, $array);
    }

    private function getResults($entity, $start, $end, $class)
    {
        $query = $this->getEntityQuery($start, $end, $entity);
        $entities = $class::searchAllTenantsQuery($entity, $query);
        $aggs = $entities['aggregations'];
        $hits = collect($entities['hits']['hits']);
        return $this->getEntityResults($entity, $aggs, $hits);
    }

    private function getCompanyResults($entity, $start, $end, $class)
    {
        $query = $this->getCompanyEntityQuery($start, $end, $entity);
        $entities = $class::searchBySingleQuery($query);
        $aggs = $entities['aggregations'];
        $hits = collect($entities['hits']['hits']);
        return $this->getCompanyEntityResults($entity, $aggs, $hits);
    }

    private function getEntityQuery($start, $end, $entity)
    {
        $excludeQuery = [];
        $includeQuery = [['match' =>  ['intra_company' => false]]];

        if ($entity == 'invoices') {
            array_push($excludeQuery, ['match' => ['type' => InvoiceType::accpay()->getIndex()]]);
        }

        if ($entity == 'purchase_orders') {
            array_push($includeQuery, ['match' =>  ['is_contractor' => false]]);
        }

        if ($this->isEntityDocumentType($entity)) {
            array_push($excludeQuery, ['match' => ['status' => $this->getEntityDraftIndex($entity)]]);
        }

        if (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_price = 'total_price';
            $shadow_price = 'shadow_price';
        } else {
            $total_price = 'total_price_usd';
            $shadow_price = 'shadow_price_usd';
        }

        return [
          'size' => 10000,
          'query' => [
              'bool' => [
                  'must' => $includeQuery,
                  'must_not' => $excludeQuery,
                  'filter' => [
                      'range' => [
                          'date' => [
                              'gte' => $start,
                              'lt' => $end
                          ]
                      ],
                  ],
              ],
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
              'by_company' => [
                  'terms' => [
                      'field' => '_index',
                      'size' => 1000
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
                      'by_customer' => [
                          'terms' => [
                              'field' => 'customer_id',
                              'missing' => 'General purchase orders',
                              'size' => 10000
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
                          ]
                      ]
                  ]
              ],
          ]
        ];
    }

    private function getCompanyEntityQuery($start, $end, $entity)
    {
        $excludeQuery = [];
        $includeQuery = [['match' =>  ['intra_company' => false]]];

        if ($entity == 'invoices') {
            array_push($includeQuery, ['match' => ['type' => InvoiceType::accrec()->getIndex()]]);
        }

        if ($entity == 'purchase_orders') {
            array_push($includeQuery, ['match' => ['is_contractor' => false]]);
        }

        if ($this->isEntityDocumentType($entity)) {
            array_push($excludeQuery, ['match' => ['status' => $this->getEntityDraftIndex($entity)]]);
        }

        if (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_price = 'total_price';
            $shadow_price = 'shadow_price';
        } else {
            $total_price = 'total_price_usd';
            $shadow_price = 'shadow_price_usd';
        }

        $query = [
          'size' => 10000,
          'query' => [
              'bool' => [
                  'must' => $includeQuery,
                  'must_not' => $excludeQuery,
                  'filter' => [
                      'range' => [
                          'date' => [
                              'gte' => $start,
                              'lt' => $end
                          ]
                      ],
                  ],
              ],
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
              'by_customer' => [
                  'terms' => [
                      'field' => 'customer_id',
                      'size' => 10000,
                      'order' => ['_key' => 'asc'],
                      'missing' => 'General purchase orders'
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
                  ]
              ]
          ]
        ];

        return $query;
    }

    private function getCompanyName($string)
    {
        $string = explode('_', $string)[0];
        $id = substr_replace($string, '-', 8, 0);
        $id = substr_replace($id, '-', 13, 0);
        $id = substr_replace($id, '-', 18, 0);
        $id = substr_replace($id, '-', 23, 0);

        $company = Company::find($id);
        return $company ? $company : 'deleted company';
    }

    private function getEntityResults($entity, $aggs, $hits)
    {
        if (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_price = 'total_price';
            $shadow_price = 'shadow_price';
        } else {
            $total_price = 'total_price_usd';
            $shadow_price = 'shadow_price_usd';
        }

        return [
          'total' => ceiling($aggs['monetary_value']['value'], 2),
          'series' => $aggs['by_company']['buckets'] = array_map(function ($r) use ($hits, $entity, $total_price, $shadow_price) {
              $m['company_name'] = $this->getCompanyName($r['key'])->name ?? $this->getCompanyName($r['key']);
              $m['company_id'] = $this->getCompanyName($r['key'])->id ?? $this->getCompanyName($r['key']);
              $m['count'] = $r['doc_count'];
              $m['monetary_value'] = ceiling($r['monetary_value']['value'], 2);
              $m['customers'] = array_map(function ($k) use ($hits, $entity, $total_price, $shadow_price) {
                if ($k['key'] == 'General purchase orders') {
                    $k['key'] = null;
                }
                  $quotes = $hits->where('_source.customer_id', $k['key']);
                  $s['customer_name'] = $k['key'] == null ? 'General purchase orders' : $quotes->first()['_source']['customer'];
                  $s['customer_id'] = $k['key'] == null ? 'General purchase orders' : $k['key'];
                  $s['count'] = $k['doc_count'];
                  $s['monetary_value'] = ceiling($k['monetary_value']['value'], 2);
                  $s[$entity] = $quotes->map(function ($quote) use ($total_price, $shadow_price) {
                      $q['id'] = $quote['_source']['id'];
                      $q['number'] = $quote['_source']['number'];
                      $q['price'] = $quote['_source'][$total_price] - $quote['_source'][$shadow_price];
                      return $q;
                  })->toArray();
                  return $s;
              }, $r['by_customer']['buckets']);

              return $m;
          }, $aggs['by_company']['buckets'])
        ];
    }

    private function getCompanyEntityResults($entity, $aggs, $hits)
    {
        if (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_price = 'total_price';
            $shadow_price = 'shadow_price';
        } else {
            $total_price = 'total_price_usd';
            $shadow_price = 'shadow_price_usd';
        }

        return [
          'total' => ceiling($aggs['monetary_value']['value'], 2),
          'series' =>
          $aggs['by_customer']['buckets'] = array_map(function ($r) use ($hits, $entity, $total_price, $shadow_price) {
            if ($r['key'] == 'General purchase orders') {
                $r['key'] = null;
            }
              $quotes = $hits->where('_source.customer_id', $r['key']);
              $m['customer_name'] = $r['key'] == null ? 'General purchase orders' : $quotes->first()['_source']['customer'];
              $m['customer_id'] = $r['key'] == null ? 'General purchase orders' : $r['key'];
              $m['count'] = $r['doc_count'];
              $m['monetary_value'] = ceiling($r['monetary_value']['value'], 2);
              $m[$entity] = $quotes->map(function ($quote) use ($total_price, $shadow_price) {
                  $q['id'] = $quote['_source']['id'];
                  $q['number'] = $quote['_source']['number'];
                  $q['price'] = $quote['_source'][$total_price] - $quote['_source'][$shadow_price];
                  return $q;
              })->toArray();
              return $m;
          }, $aggs['by_customer']['buckets'])
        ];
    }

    private function getInvoiceTotalEntitiesQuery($array, $terms, $noDrafts, $before = false)
    {

        $interval = $array['interval'];
        $format = $array['format'];
        if ($before) {
            $min = $array['before_min'];
            $max = $array['before_max'];
            $start = $array['before_start'];
        } else {
            $min = $array['min'];
            $max = $array['max'];
            $start = $array['start'];
        }


        extract(getFormatedFieldsWithUserCurrency());

        return [
          'monetary_value' => [
              'sum' => [
                  'script' => [
                      'lang' => 'painless',
                      'inline' => "doc['$total_price'].value - doc['$shadow_price'].value"
                  ]
              ]
          ],
          'partial_monetary_value' => [
              'sum' => [
                  'field' => $total_paid_amount
              ]
          ],
          'partial_vat_value' => [
              'sum' => [
                  'field' => $total_paid_vat
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
          'entities_per' => [
              'date_histogram' => [
                  'field' => 'date',
                  'interval' => $interval,
                  'min_doc_count' => 0,
                  'extended_bounds' => [
                      'min' => $min,
                      'max' => $max
                  ],
                  'format' => $format,
              ],
              'aggs' => [
                  'outgoing' => [
                      'filter' => ['terms' => ['type' => [InvoiceType::accrec()->getIndex()]]],
                      'aggs' => [
                          'without_drafts' => [
                              'filter' => ['terms' => ['status' => $noDrafts]],
                              'aggs' => [
                                  'monetary_value' => [
                                      'sum' => [
                                          'script' => [
                                              'lang' => 'painless',
                                              'inline' => "doc['$total_price'].value - doc['$shadow_price'].value"
                                          ]
                                      ]
                                  ],
                                  'partial_monetary_value' => [
                                      'sum' => [
                                          'field' => $total_paid_amount
                                      ]
                                  ],
                                  'partial_vat_value' => [
                                      'sum' => [
                                          'field' => $total_paid_vat
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
                  ],
                  'incoming' => [
                      'filter' => ['terms' => ['type' => [InvoiceType::accpay()->getIndex()]]],
                      'aggs' => [
                          'without_drafts' => [
                              'filter' => ['terms' => ['status' => $noDrafts]],
                              'aggs' => [
                                  'monetary_value' => [
                                      'sum' => [
                                          'field' => $total_price
                                      ]
                                  ],
                                  'partial_monetary_value' => [
                                      'sum' => [
                                          'field' => $total_paid_amount
                                      ]
                                  ],
                                  'partial_vat_value' => [
                                      'sum' => [
                                          'field' => $total_paid_vat
                                      ]
                                  ],
                                  'vat_value' => [
                                      'sum' => [
                                          'field' => $total_vat
                                      ]
                                  ],
                                  'po_invoices' => [
                                      'filter' => [
                                          'exists' => ['field' => 'purchase_order_id']
                                      ],
                                      'aggs' => [
                                          'monetary_value' => [
                                              'sum' => [
                                                  'field' => $total_price
                                              ]
                                          ],
                                          'partial_monetary_value' => [
                                              'sum' => [
                                                  'field' => $total_paid_amount
                                              ]
                                          ],
                                          'partial_vat_value' => [
                                              'sum' => [
                                                  'field' => $total_paid_vat
                                              ]
                                          ],
                                          'vat_value' => [
                                              'sum' => [
                                                  'field' => $total_vat
                                              ]
                                          ],
                                      ]
                                  ]
                              ]
                          ],
                      ]
                  ],
              ],
          ],
          'term_entities' => [
              'filter' => ['terms' => ['type' => [InvoiceType::accrec()->getIndex()]]],
              'aggs' => [
                  'monetary_value' => [
                      'sum' => [
                          'script' => [
                              'lang' => 'painless',
                              'inline' => "doc['$total_price'].value - doc['$shadow_price'].value"
                          ]
                      ]
                  ],
                  'partial_monetary_value' => [
                      'sum' => [
                          'field' => $total_paid_amount
                      ]
                  ],
                  'partial_vat_value' => [
                      'sum' => [
                          'field' => $total_paid_vat
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
                  'by_status' => [
                      'terms' => [
                          'field' => 'status',
                          'include' => $terms,
                          'min_doc_count' => 0,
                          'order' => [
                              '_key' => 'asc'
                          ],
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
                          'partial_monetary_value' => [
                              'sum' => [
                                  'field' => $total_paid_amount
                              ]
                          ],
                          'partial_vat_value' => [
                              'sum' => [
                                  'field' => $total_paid_vat
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
                  'not_paid' => [
                      'missing' => ['field' => 'pay_date'],
                      'aggs' => [
                          'only_sent' => [
                              'filter' => ['terms' => ['status' => [InvoiceStatus::submitted()->getIndex(), InvoiceStatus::partial_paid()->getIndex()]]],
                              'aggs' => [
                                  'overdue' => [
                                      'terms' => [
                                          'min_doc_count' => 0,
                                          'order' => [
                                              '_key' => 'desc'
                                          ],
                                          'script' => [
                                              'inline' => "doc['due_date'].value.getMillis() < params['now'] && doc['due_date'].value.getMillis() >= params['start']",
                                              'params' => [
                                                  'now' => round(microtime(true) * 1000),
                                                  'start' => round($start * 1000)
                                              ]
                                          ],
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
                                          'partial_monetary_value' => [
                                              'sum' => [
                                                  'field' => $total_paid_amount
                                              ]
                                          ],
                                          'partial_vat_value' => [
                                              'sum' => [
                                                  'field' => $total_paid_vat
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
                  ]
              ]
          ],
        ];
    }


    private function getInvoiceQuery($array, $terms, $noDrafts)
    {
        //dd($array);
        $start = $array['start'];
        $end = $array['end'];
        $interval = $array['interval'];
        $format = $array['format'];
        $min = $array['min'];
        $max = $array['max'];
        $before_start = $array['before_start'];
        $before_end = $array['before_end'];


        if (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_price = 'total_price';
            $total_vat = 'total_vat';
            $shadow_price = 'shadow_price';
            $shadow_vat = 'shadow_vat';
            $total_paid_amount = 'total_paid_amount';
            $total_paid_vat = 'total_paid_vat';
            $gross_margin = 'gross_margin';
        } else {
            $total_price = 'total_price_usd';
            $total_vat = 'total_vat_usd';
            $shadow_price = 'shadow_price_usd';
            $shadow_vat = 'shadow_vat_usd';
            $total_paid_amount = 'total_paid_amount_usd';
            $total_paid_vat = 'total_paid_vat_usd';
            $gross_margin = 'gross_margin_usd';
        }

        return [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => [
                      ['terms' => [
                          'status' => [
                              InvoiceStatus::draft()->getIndex(), InvoiceStatus::approval()->getIndex(), InvoiceStatus::rejected()->getIndex(),
                              InvoiceStatus::authorised()->getIndex(), InvoiceStatus::submitted()->getIndex(),InvoiceStatus::unpaid()->getIndex(),
                              InvoiceStatus::paid()->getIndex(), InvoiceStatus::partial_paid()->getIndex(), InvoiceStatus::cancelled()->getIndex()
                          ]
                      ]],
                      [
                        'terms' => [
                                'type' => [ InvoiceType::accrec()->getIndex() ]
                            ]
                        ]
                  ],
                  'must_not'=>[
                      [
                          'terms' => [
                              'shadow' => [ true ]
                          ]
                      ]
                  ]
              ]
          ],
          'aggs' => [
              'intra_company'=>[
                  'filter'=>[
                      'bool'=>[
                          'must'=>[
                              ['terms' => ['intra_company' => [true]]],
                              [
                                  'range' => [
                                      'date' =>  [
                                          'gte' => $start,
                                          'lte' => $end,
                                      ]
                                  ],
                              ],
                          ],
                      ]
                  ],
                  'aggs' =>[
                      'monetary_value' => [
                          'sum' => [
                              'script' => [
                                  'lang' => 'painless',
                                  'inline' => "doc['$total_price'].value - doc['$shadow_price'].value"
                              ]
                          ]
                      ],
                      'partial_monetary_value' => [
                          'sum' => [
                              'field' => $total_paid_amount
                          ]
                      ],
                      'partial_vat_value' => [
                          'sum' => [
                              'field' => $total_paid_vat
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
              'all' => [
                  'filter'=>[
                      'bool' => [
                          'must_not' => [
                              ['terms' => [
                                  'status' => [
                                      InvoiceStatus::draft()->getIndex()
                                  ]
                              ]]
                          ]
                      ]
                  ],
                  'aggs'=> [
                      'total_entities'=> [
                          'filter'=>[
                              'bool'=>[
                                  'must'=>[
                                      [
                                          'range' => [
                                              'date' =>  [
                                                  'gte' => $start,
                                                  'lte' => $end,
                                              ]
                                          ],
                                      ],
                                  ],
                              ]
                          ],
                          'aggs' =>$this->getInvoiceTotalEntitiesQuery($array, $terms, $noDrafts)
                      ],
                      'intra_company'=>[
                          'filter'=>[
                              'bool'=>[
                                  'must'=>[
                                      ['terms' => ['intra_company' => [true]]],
                                  ],
                              ]
                          ],
                          'aggs' =>[
                              'monetary_value' => [
                                  'sum' => [
                                      'script' => [
                                          'lang' => 'painless',
                                          'inline' => "doc['$total_price'].value - doc['$shadow_price'].value"
                                      ]
                                  ]
                              ],
                              'partial_monetary_value' => [
                                  'sum' => [
                                      'field' => $total_paid_amount
                                  ]
                              ],
                              'partial_vat_value' => [
                                  'sum' => [
                                      'field' => $total_paid_vat
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
                      'current_year' => [
                          'filter'=>[
                              'bool'=>[
                                  'must'=>[
                                      [
                                          'range' => [
                                              'date' =>  [
                                                  'gte' => $start,
                                                  'lte' => $end,
                                              ]
                                          ],
                                      ],
                                      ['match' => ['intra_company' => false]]
                                  ],
                              ]
                          ],
                          'aggs' => $this->getInvoiceTotalEntitiesQuery($array, $terms, $noDrafts, false)
                      ],
                      'total_overdues' => [
                        'filter'=>[
                            'bool'=>[
                                'must'=>[
                                    ['match' => ['intra_company' => false]]

                                ],
                            ]
                        ],
                        'aggs' => $this->getInvoiceTotalEntitiesQuery($array, $terms, $noDrafts, false)
                      ],
                      'intra_company_current_year' => [
                          'filter'=>[
                              'bool'=>[
                                  'must'=>[
                                      [
                                          'range' => [
                                              'date' =>  [
                                                  'gte' => $start,
                                                  'lte' => $end,
                                              ]
                                          ],
                                      ],
                                      ['match' => ['intra_company' => true]]
                                  ],
                              ]
                          ],
                          'aggs' => $this->getInvoiceTotalEntitiesQuery($array, $terms, $noDrafts, false)
                      ],
                      'year_before' => [
                          'filter'=>[
                              'bool'=>[
                                  'must'=>[
                                      [
                                          'range' => [
                                              'date' =>  [
                                                  'gte' => $before_start,
                                                  'lte' => $before_end,
                                              ]
                                          ],
                                      ],
                                      ['match' => ['intra_company' => false]]
                                  ],
                              ]
                          ],
                          'aggs' => $this->getInvoiceTotalEntitiesQuery($array, $terms, $noDrafts, true)
                      ],
                      'intra_company_year_before' => [
                          'filter'=>[
                              'bool'=>[
                                  'must'=>[
                                      ['match' => ['type' => InvoiceType::accrec()->getIndex()]],
                                      [
                                          'range' => [
                                              'date' =>  [
                                                  'gte' => $before_start,
                                                  'lte' => $before_end,
                                              ]
                                          ],
                                      ],
                                      ['match' => ['intra_company' => true]]
                                  ],
                              ]
                          ],
                          'aggs' => $this->getInvoiceTotalEntitiesQuery($array, $terms, $noDrafts, true)
                      ],
                  ]
              ],
              'default' => [
                  'filter'=>[
                      'bool' => [
                          'must' => [
                              ['terms' => [
                                  'status' => [
                                      InvoiceStatus::draft()->getIndex(), InvoiceStatus::approval()->getIndex(),
                                      InvoiceStatus::authorised()->getIndex(), InvoiceStatus::submitted()->getIndex(),
                                      InvoiceStatus::paid()->getIndex(), InvoiceStatus::partial_paid()->getIndex(),
                                      InvoiceStatus::unpaid()->getIndex()
                                  ]
                              ]]
                          ]
                      ]
                  ],
                  'aggs'=> [
                      'entities_by_status' => [
                          'filters' => $this->buildFilteringsSubQuery(InvoiceStatus::class, $start, $end, false),
                          'aggs'=> $this->getInvoiceTotalEntitiesQuery($array, $terms, $noDrafts)
                      ],
                      'intra_company_entities_by_status' => [
                          'filters' => $this->buildFilteringsSubQuery(InvoiceStatus::class, $start, $end, true),
                          'aggs'=> $this->getInvoiceTotalEntitiesQuery($array, $terms, $noDrafts)
                      ],
                      'normal_total_entities' => [
                          'date_range' => [
                              'field' => 'date',
                              'ranges' => [
                                  ['from' => $start, 'to' => $end],
                              ],
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
                              'partial_monetary_value' => [
                                  'sum' => [
                                      'field' => $total_paid_amount
                                  ]
                              ],
                              'partial_vat_value' => [
                                  'sum' => [
                                      'field' => $total_paid_vat
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
                              'entities_per' => [
                                  'date_histogram' => [
                                      'field' => 'date',
                                      'interval' => $interval,
                                      'min_doc_count' => 0,
                                      'extended_bounds' => [
                                          'min' => $min,
                                          'max' => $max
                                      ],
                                      'format' => $format,
                                  ],
                                  'aggs' => [
                                      'outgoing' => [
                                          'filter' => ['terms' => ['type' => [InvoiceType::accrec()->getIndex()]]],
                                          'aggs' => [
                                              'intra_company_without_drafts' => [
                                                  'filter' => [
                                                      'bool' =>[
                                                          'must'=>[
                                                              ['terms' => ['status' => $noDrafts]],
                                                              ['terms' => ['intra_company' => [true]]]
                                                          ]
                                                      ]
                                                  ],
                                                  'aggs' => [
                                                      'gross_margin' => [
                                                          'sum' => [
                                                              'script' => [
                                                                  'lang' => 'painless',
                                                                  'inline' => "doc['$gross_margin'].value"
                                                              ]
                                                          ]
                                                      ],
                                                      'gross_percent' => [
                                                          'sum' => [
                                                              'script' => [
                                                                  'lang' => 'painless',
                                                                  'inline' => "doc['$gross_margin'].value"
                                                              ]
                                                          ]
                                                      ],
                                                      'monetary_value' => [
                                                          'sum' => [
                                                              'script' => [
                                                                  'lang' => 'painless',
                                                                  'inline' => "doc['$total_price'].value - doc['$shadow_price'].value"
                                                              ]
                                                          ]
                                                      ],
                                                      'partial_monetary_value' => [
                                                          'sum' => [
                                                              'field' => $total_paid_amount
                                                          ]
                                                      ],
                                                      'partial_vat_value' => [
                                                          'sum' => [
                                                              'field' => $total_paid_vat
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
                                              'without_drafts' => [
                                                  'filter' => [
                                                      'bool' =>[
                                                          'must'=>[
                                                              ['terms' => ['status' => $noDrafts]],
                                                              ['terms' => ['intra_company' => [false]]]
                                                          ]
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
                                                      'partial_monetary_value' => [
                                                          'sum' => [
                                                              'field' => $total_paid_amount
                                                          ]
                                                      ],
                                                      'partial_vat_value' => [
                                                          'sum' => [
                                                              'field' => $total_paid_vat
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
                                      ],
                                      'incoming' => [
                                          'filter' => ['terms' => ['type' => [InvoiceType::accpay()->getIndex()]]],
                                          'aggs' => [
                                              'intra_compay_without_drafts' => [
                                                  'filter' => [
                                                      'bool' =>[
                                                          'must'=>[
                                                              ['terms' => ['status' => $noDrafts]],
                                                              ['terms' => ['intra_company' => [true]]]
                                                          ]
                                                      ]
                                                  ],
                                                  'aggs' => [
                                                      'monetary_value' => [
                                                          'sum' => [
                                                              'field' => $total_price
                                                          ]
                                                      ],
                                                      'partial_monetary_value' => [
                                                          'sum' => [
                                                              'field' => $total_paid_amount
                                                          ]
                                                      ],
                                                      'partial_vat_value' => [
                                                          'sum' => [
                                                              'field' => $total_paid_vat
                                                          ]
                                                      ],
                                                      'vat_value' => [
                                                          'sum' => [
                                                              'field' => $total_vat
                                                          ]
                                                      ],
                                                      'po_invoices' => [
                                                          'filter' => [
                                                              'exists' => ['field' => 'purchase_order_id']
                                                          ],
                                                          'aggs' => [
                                                              'monetary_value' => [
                                                                  'sum' => [
                                                                      'field' => $total_price
                                                                  ]
                                                              ],
                                                              'partial_monetary_value' => [
                                                                  'sum' => [
                                                                      'field' => $total_paid_amount
                                                                  ]
                                                              ],
                                                              'partial_vat_value' => [
                                                                  'sum' => [
                                                                      'field' => $total_paid_vat
                                                                  ]
                                                              ],
                                                              'vat_value' => [
                                                                  'sum' => [
                                                                      'field' => $total_vat
                                                                  ]
                                                              ],
                                                          ]
                                                      ]
                                                  ]
                                              ],
                                              'without_drafts' => [
                                                  'filter' => [
                                                      'bool' =>[
                                                          'must'=>[
                                                              ['terms' => ['status' => $noDrafts]],
                                                              ['terms' => ['intra_company' => [false]]]
                                                          ]
                                                      ]
                                                  ],
                                                  'aggs' => [
                                                      'monetary_value' => [
                                                          'sum' => [
                                                              'field' => $total_price
                                                          ]
                                                      ],
                                                      'partial_monetary_value' => [
                                                          'sum' => [
                                                              'field' => $total_paid_amount
                                                          ]
                                                      ],
                                                      'partial_vat_value' => [
                                                          'sum' => [
                                                              'field' => $total_paid_vat
                                                          ]
                                                      ],
                                                      'vat_value' => [
                                                          'sum' => [
                                                              'field' => $total_vat
                                                          ]
                                                      ],
                                                      'po_invoices' => [
                                                          'filter' => [
                                                              'exists' => ['field' => 'purchase_order_id']
                                                          ],
                                                          'aggs' => [
                                                              'monetary_value' => [
                                                                  'sum' => [
                                                                      'field' => $total_price
                                                                  ]
                                                              ],
                                                              'partial_monetary_value' => [
                                                                  'sum' => [
                                                                      'field' => $total_paid_amount
                                                                  ]
                                                              ],
                                                              'partial_vat_value' => [
                                                                  'sum' => [
                                                                      'field' => $total_paid_vat
                                                                  ]
                                                              ],
                                                              'vat_value' => [
                                                                  'sum' => [
                                                                      'field' => $total_vat
                                                                  ]
                                                              ],
                                                          ]
                                                      ]
                                                  ]
                                              ],
                                          ]
                                      ],
                                  ],
                              ],
                          ]
                      ],
                  ]
              ],
          ]
        ];
    }

    public function employeeQuery($start, $end, $single = false, $periodEnded = false, $endOfEarnOutDate = null)
    {
        if (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex()) {
            $salary = 'salary';
        } else {
            $salary = 'salary_usd';
        }

        if ($periodEnded) {
            $endOfPeriod = $endOfEarnOutDate;
            $size = 1000;
        } else {
            $endOfPeriod = $end;
            $size = 0;
        }

        $query = [
          'size' => $size,
          'query' => [
              'bool' => [
                  'must_not' => [
                      [
                          'range' => [
                              'end_date' => [
                                  'lte' => $start
                              ]
                          ]
                      ],
                      [
                          'range' => [
                              'start_date' => [
                                  'gte' => $endOfPeriod
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
                          ['from' => $start, 'to' => $endOfPeriod],
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
                          ['from' => $start, 'to' => $end],
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

        if ($single) {
            return EmployeeHistory::searchBySingleQuery($query);
        } else {
            return EmployeeHistory::searchAllTenantsQuery('employee_histories', $query);
        }
    }

    private function getCustomers($entity, $startDate, $endDate)
    {
        $invoiceQuery = [];
        if ($entity == Invoice::class) {
            array_push($invoiceQuery, ['match' => ['type' => InvoiceType::accpay()->getIndex()]]);
        }
        $query = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must_not' => $invoiceQuery,
                  'filter' => [
                      'range' => [
                          'date' => [
                              'gte' => $startDate,
                              'lt' => $endDate
                          ]
                      ],
                  ],
              ],
          ],
          'aggs' => [
              'by_customer' => [
                  'terms' => [
                      'field' => 'customer_id',
                      'size' => 10000,
                      'order' => ['_key' => 'asc']
                  ],
              ]
          ]
        ];

        $results = $entity::searchBySingleQuery($query);
        $customers = $results['aggregations']['by_customer']['buckets'];
        $customers = array_map(function ($r) {
            $r = Arr::only($r, 'key');
            return $r;
        }, $customers);
        return Arr::flatten($customers);
    }

    private function getEarnoutsInPeriod($start, $end, $orderIds, $confirmed = false)
    {
        $orderQuery = [];
        if ($confirmed) {
            $extendedDate = strtotime('+20 days', $start);
        } else {
            $extendedDate = strtotime('+40 days', $start);
        }

        if (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex()) {
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
        if ($orderIds) {
            array_push($orderQuery, ['bool' => ['should' => []]]);
            foreach ($orderIds as $order) {
                array_push($orderQuery[0]['bool']['should'], [
                'match' => ['order_id' => $order]
                ]);
            }
        }

        $query = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => [
                      $orderQuery,
                      [
                          'bool' => [
                              'must' => [
                                  'match' => ['status' => InvoiceStatus::paid()->getIndex()]
                              ],
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  'match' => ['eligible_for_earnout' => true]
                              ],
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
                                                          'gte' => $start,
                                                          'lte' => $extendedDate
                                                      ]
                                                  ],
                                              ],
                                              [
                                                  'range' => [
                                                      'date' => [
                                                          'gte' => $start,
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
                                                          'lte' => $end
                                                      ]
                                                  ]
                                              ]
                                          ]
                                      ],
                                  ]
                              ],
                          ],
                      ],
                  ]
              ]
          ],
          'aggs' => [
              'total_earnouts' => [
                  'filter' => ['terms' => ['type' => [InvoiceType::accrec()->getIndex()]]],
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
                  ],
              ],
          ],
        ];

        return Invoice::searchBySingleQuery($query);
    }

    private function getOrdersForEarnoutQuery($startDate, $endDate)
    {
        if ($endDate > time()) {
            $endDate = time();
        }

        $min = date('Y-m', $startDate);
        $max = date('Y-m', $endDate);
        $extendedDate = strtotime('+40 days', $startDate);

        return [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => [
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
                  ]
              ]
          ],
          'aggs' => [
              'total_earnouts' => [
                  'date_range' => [
                      'field' => 'pay_date',
                      'ranges' => [
                          ['from' => $startDate, 'to' => $endDate],
                      ],
                  ],
                  'aggs' => [
                      'earnouts_per' => [
                          'date_histogram' => [
                              'field' => 'pay_date',
                              'interval' => 'quarter',
                              'min_doc_count' => 0,
                              'extended_bounds' => [
                                  'min' => $min,
                                  'max' => $max
                              ],
                              'format' => 'yyyy-MM',
                          ],
                          'aggs' => [
                              'orders' => [
                                  'terms' => [
                                      'field' => 'order_id',
                                      'size' => 10000,
                                  ],
                              ],
                          ]
                      ]
                  ]
              ]
          ]
        ];
    }

    private function getEarnoutSeries($ordersPerInterval, $company, $data = null, $loanAmountBefore = 0)
    {
        $earnOutStatusRepository = App::make(EarnOutStatusRepositoryInterface::class);
        $loanAmountLeft = $loanAmountBefore;
        $x = 0;
        $calculateInNextPeriod = false;
        $acquisitionDate = strtotime($company->acquisition_date);
        $endOfEarnoutPeriod = strtotime('+' . $company->earnout_years . ' years', $acquisitionDate);

        $loans = CompanyLoan::all();

        return array_map(function ($r) use ($loans, &$loanAmountLeft, $data, &$x, $company, $endOfEarnoutPeriod, $acquisitionDate, &$calculateInNextPeriod, $earnOutStatusRepository) {
            $m['name'] = date('F', strtotime($r['key_as_string']));
            $year = date('Y', strtotime($r['key_as_string']));
            switch ($m['name']) {
                case 'January':
                    $m['name'] = 'Q1 ' . $year;
                    break;
                case 'April':
                    $m['name'] = 'Q2 ' . $year;
                    break;
                case 'July':
                    $m['name'] = 'Q3 ' . $year;
                    break;
                case 'October':
                    $m['name'] = 'Q4 ' . $year;
                    break;
            }

            $orderIds = array_map(function ($order) {
                return $order['key'];
            }, $r['orders']['buckets']);
            $endKey = strtotime('+3 month', $r['key'] / 1000);
            $startDate = date('Y-m-d', $r['key'] / 1000);
            $endDate = date('Y-m-d', $endKey);
            $periodEnded = false;
            $periodStarted = false;
            $doNotCalculate = false;
            $confirmed = false;
            $approvalDate = $earnOutStatusRepository->firstByOrNull('quarter', $r['key'] / 1000);
            if ($approvalDate && $approvalDate->approved < '2021-12-31') {
                $extendedSearch = strtotime($approvalDate->approved);
                $confirmed = true;
            } else {
                $extendedSearch = strtotime('+40 days', $endKey);
            }

            if ($acquisitionDate >= $r['key'] / 1000 && $acquisitionDate <= $endKey) {
                $periodStarted = true;
            }

            if ($endOfEarnoutPeriod >= $r['key'] / 1000 && $endOfEarnoutPeriod <= $endKey) {
                $periodEnded = true;
                $extendedSearch = strtotime('+3 months', $endOfEarnoutPeriod);
            }

            if ($calculateInNextPeriod) {
                $r['key'] = $acquisitionDate * 1000;
            }

            if ($endOfEarnoutPeriod < $r['key'] / 1000) {
                $doNotCalculate = true;
            }

            $earnoutsLoan = 0;
            $totalGrossMarginBonus = 0;
            $totalEarnoutBonus = 0;

            if ($loans->isNotEmpty()) {
                $extraLoanAmount = 0;

                foreach ($loans as $loan) {
                    $paid = 0;
                    if ($loan->issued_at >= $startDate && $loan->issued_at < $endDate) {
                        $logs = $loan->paymentLogs->where('pay_date', '<', $endDate);
                        if ($logs->isNotEmpty()) {
                            $paid = UserRole::isAdmin(auth()->user()->role) ? $logs->sum('admin_amount') : $logs->sum('amount');
                        }

                        $extraLoanAmount += UserRole::isAdmin(auth()->user()->role) ? $loan->admin_amount - $paid :
                        $loan->amount - $paid;
                        ;
                    }
                }

                $loanAmountLeft += $extraLoanAmount;
            }

            if (!$doNotCalculate) {
                if (!$periodStarted) {
                    if ($loans->isNotEmpty() && ($orderIds || $periodEnded || $calculateInNextPeriod || $extendedSearch)) {
                        $earnoutsUsedForLoan = 0;

                        $earnoutsWithoutLoanArray = $this->getEarnoutDataForPeriod($r['key'] / 1000, $endKey, $company, $periodEnded, $extendedSearch, $confirmed);
                        $earnoutsWithoutLoan = $earnoutsWithoutLoanArray['earnout_bonus'] + $earnoutsWithoutLoanArray['gross_margin_bonus'];

                        if ($loanAmountLeft > 0 && $earnoutsWithoutLoan > 0) {
                            $loanLeftToPay = $loanAmountLeft;
                            $loanLeftToPay -= $earnoutsWithoutLoan;
                            if ($loanLeftToPay <= 0) {
                                  $earnoutsUsedForLoan = $loanAmountLeft;
                                  $loanAmountLeft = 0;
                            } else {
                                $earnoutsUsedForLoan = $earnoutsWithoutLoan;
                                $loanAmountLeft = $loanLeftToPay;
                            }
                        }

                        $earnoutsLoan += $earnoutsUsedForLoan;
                        $totalGrossMarginBonus = $earnoutsWithoutLoanArray['gross_margin_bonus'];
                        $totalEarnoutBonus = $earnoutsWithoutLoanArray['earnout_bonus'];
                    } else {
                        if ($periodEnded || $orderIds || $calculateInNextPeriod || $extendedSearch) {
                            if ($periodEnded || $calculateInNextPeriod || $extendedSearch) {
                                $earnoutsWithoutLoanArray = $this->getEarnoutDataForPeriod($r['key'] / 1000, $endKey, $company, $periodEnded, $extendedSearch, $confirmed);
                            } else {
                                $earnoutsWithoutLoanArray = $this->getEarnoutBonus($r['key'] / 1000, $endKey, $orderIds, $company);
                            }
                            $totalGrossMarginBonus = $earnoutsWithoutLoanArray['gross_margin_bonus'];
                            $totalEarnoutBonus = $earnoutsWithoutLoanArray['earnout_bonus'];
                        }
                    }
                    $calculateInNextPeriod = false;
                } else {
                    $calculateInNextPeriod = true;
                }
            }

            $m['loan_substraction'] = ceiling($earnoutsLoan + ($data ? $data[$x]['series'][0]['value'] : 0), 2);
            $m['gross_margin_bonus'] = ceiling($totalGrossMarginBonus + ($data ? $data[$x]['series'][1]['value'] : 0), 2);
            $m['earnout_bonus'] = ceiling($totalEarnoutBonus + ($data ? $data[$x]['series'][2]['value'] : 0), 2);

            $item = [
              'name' => 'loan_substracted_earnout',
              'value' => $m['loan_substraction']
            ];
            $item2 = [
              'name' => 'gross_margin_bonus',
              'value' => $m['gross_margin_bonus']
            ];
            $item3 = [
              'name' => 'legacy_earnout_bonus',
              'value' => $m['earnout_bonus']
            ];

            $m['series'] = [];
            array_push($m['series'], $item, $item2, $item3);
            unset($m['loan_substraction']);
            unset($m['gross_margin_bonus']);
            unset($m['earnout_bonus']);

            $x++;

            return $m;
        }, $ordersPerInterval);
    }

    public function getOrdersForLoanPeriod($startDate, $endDate, $company, $periodEnded, $dateOfApproval = null, $confirmed = false)
    {
        $approvalQuery = [];
        $acquisitionDate = strtotime($company->acquisition_date);
        $endOfEarnoutPeriod = strtotime('+' . $company->earnout_years . ' years', $acquisitionDate);
        if ($confirmed) {
            $extendedDate = strtotime('+20 days', $startDate);
        } else {
            $extendedDate = strtotime('+40 days', $startDate);
        }

        if ($periodEnded) {
            $endOfEarnoutPeriod = strtotime('+3 months', $endOfEarnoutPeriod);
            $endDate = $endOfEarnoutPeriod;
        }

        if ($dateOfApproval) {
            array_push($approvalQuery, [
            'bool' => [
                'must' => [
                    'range' => [
                        'close_date' => [
                            'gte' => $startDate,
                            'lte' => $dateOfApproval
                        ]
                    ],
                ]
            ]
            ]);
        }

        $query = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => array_merge([
                      [
                          'bool' => [
                              'must' => [
                                  'range' => [
                                      'pay_date' => [
                                          'gte' => $acquisitionDate,
                                          'lte' => $endOfEarnoutPeriod
                                      ]
                                  ],
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
                  ], $approvalQuery),
              ]
          ],
          'aggs' => [
              'orders' => [
                  'terms' => [
                      'field' => 'order_id',
                      'size' => 10000,
                  ],
              ],
          ],
        ];

        $invoices = Invoice::searchBySingleQuery($query);
        $orderIds = array_map(function ($invoice) use ($periodEnded, $endDate) {
            if ($periodEnded) {
                $order = Order::find($invoice['key']);
                if ($order && $order->delivered_at !== null) {
                    if (strtotime($order->delivered_at) < strtotime('-3 months', $endDate)) {
                        return $invoice['key'];
                    }
                }
            } else {
                return $invoice['key'];
            }
        }, $invoices['aggregations']['orders']['buckets']);

        return array_filter($orderIds);
    }

    public function getEarnoutDataForPeriod($startDate, $endDate, $company, $periodEnded, $dateOfApproval = null, $confirmed = false)
    {
        $orderIds = $this->getOrdersForLoanPeriod($startDate, $endDate, $company, $periodEnded, $dateOfApproval, $confirmed);

        if ($orderIds) {
            return $this->getEarnoutBonus($startDate, $endDate, $orderIds, $company, $periodEnded, $confirmed);
        } else {
            return [
            'earnout_bonus' => 0,
            'gross_margin_bonus' => 0,
            ];
        }
    }

    public function getEarnoutBonus($startDate, $endDate, $orderIds, $company, $periodEnded = false, $confirmed = false)
    {
        $endOfEarnoutPeriod = strtotime('+' . $company->earnout_years . ' years', strtotime($company->acquisition_date));
        $earnouts = $this->getEarnoutsInPeriod($startDate, $endDate, $orderIds, $confirmed);
        $salaries = $this->employeeQuery($startDate, $endDate, true, $periodEnded, $endOfEarnoutPeriod);
        $rentCosts = $this->getRentCosts($startDate, $endDate, $periodEnded, $endOfEarnoutPeriod);
        $internalSalaryCosts = $this->getInternalSalaryCost($salaries, $startDate, $endDate, $periodEnded, $endOfEarnoutPeriod);

        $costs = $this->getPurchaseOrderCostsForEarnOut($startDate, $endDate, $company);
        $externalSalaryCost = $this->getExternalSalaryCostsForEarnOut($startDate, $endDate, $company);
        $earnoutsTotal = $earnouts['aggregations']['total_earnouts']['monetary_value']['value']
          - $earnouts['aggregations']['total_earnouts']['credit_notes_price']['value'];
        $earnoutsVat = $earnouts['aggregations']['total_earnouts']['vat_value']['value']
          - $earnouts['aggregations']['total_earnouts']['credit_notes_vat']['value'];
        $legacy = $earnouts['aggregations']['total_earnouts']['legacy_customer']['monetary_value']['value']
          - $earnouts['aggregations']['total_earnouts']['legacy_customer']['credit_notes_price']['value']
          - $earnouts['aggregations']['total_earnouts']['legacy_customer']['vat_value']['value']
          - $earnouts['aggregations']['total_earnouts']['legacy_customer']['credit_notes_vat']['value'];

        $earnoutsTotal = $earnoutsTotal - $earnoutsVat;
        $grossMargin = round($earnoutsTotal, 2) - round($costs, 2) - round($externalSalaryCost, 2) - round($internalSalaryCosts, 2) - round($rentCosts, 2);
        $bonus = ($grossMargin * $company->gm_bonus) / 100;

        if ($bonus < 0) {
            $bonus = 0;
        }

        $legacy_bonus = ceiling(($legacy * $company->earnout_bonus) / 100, 2);
        $gross_margin_bonus = ceiling($bonus, 2);

        return [
          'earnout_bonus' => $legacy_bonus ?? 0,
          'gross_margin_bonus' => $gross_margin_bonus ?? 0,
        ];
    }

    public function getInternalSalaryCost($salaries, $startDate, $endDate, $periodEnded, $endOfEarnOutDate)
    {
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
                    $month = date('m', $isEndOfPeriod);
                    $year = date('Y', $isEndOfPeriod);
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

        if ($periodEnded) {
            if (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex()) {
                $salary = 'salary';
            } else {
                $salary = 'salary_usd';
            }

            $daysAfterEarnOut = ceil(abs($endOfEarnOutDate - $endDate) / 86400);
            foreach ($salaries['hits']['hits'] as $hit) {
                $endOfEmployment = $hit['_source']['end_date'];
                $daySalary = $hit['_source'][$salary] / 30;
                if (($endOfEmployment === null) || $endOfEmployment > $endDate) {
                    $salaryAfterEarnOut += round($daysAfterEarnOut * $daySalary, 2);
                } elseif ($endOfEmployment > $endOfEarnOutDate && $endOfEmployment < $endDate) {
                    $daysAfter = ceil(abs($endOfEarnOutDate - $endOfEmployment) / 86400);
                    $salaryAfterEarnOut += round($daysAfter * $daySalary, 2);
                }
            }
        }

        return $totalSalary - $sumStart - $sumEnd - $salaryAfterEarnOut;
    }

    public function getRentCosts($startDate, $endDate, $periodEnded, $endOfEarnOutDate)
    {
        if (UserRole::isAdmin(auth()->user()->role)) {
            $amount = 'admin_amount';
        } else {
            $amount = 'amount';
        }

        if ($periodEnded) {
            $endOfPeriod = $endOfEarnOutDate;
            $size = 1000;
        } else {
            $endOfPeriod = $endDate;
            $size = 0;
        }

        $query = [
          'size' => $size,
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
                                  'gte' => $endOfPeriod
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
              'costs' => [
                  'sum' => [
                      'field' => $amount
                  ]
              ],
              'started_in_period' => [
                  'date_range' => [
                      'field' => 'start_date',
                      'ranges' => [
                          ['from' => $startDate, 'to' => $endOfPeriod],
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
            $notFullRentStart = array_map(function ($k) use ($startOfMonth) {
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

        if ($periodEnded) {
            $daysAfterEarnOut = ceil(abs($endOfPeriod - $endDate) / 86400);
            foreach ($rents['hits']['hits'] as $hit) {
                $endOfRent = $hit['_source']['end_date'];
                $dayRent = $hit['_source'][$amount] / 30;

                if (($endOfRent === null) || $endOfRent > $endDate) {
                    $rentAfterEarnOut += $daysAfterEarnOut * $dayRent;
                } elseif ($endOfRent > $endOfPeriod && $endOfRent < $endDate) {
                    $daysAfter = ceil(abs($endOfPeriod - $endOfRent) / 86400);
                    $rentAfterEarnOut += $daysAfter * $dayRent;
                }
            }
        }

        return $totalRent - $sumStart - $sumEnd - $rentAfterEarnOut;
    }

    private function isEntityDocumentType(string $entity): bool
    {
        return in_array($entity, ['quotes', 'orders', 'purchase_orders', 'invoices', 'invoice_payments']);
    }

    private function getEntityCancelledIndex(string $entity): int
    {
        if ($entity === 'quotes' || $entity === Quote::class) {
            $cancelledStatus = QuoteStatus::cancelled()->getIndex();
        } elseif ($entity === 'orders' || $entity === Order::class) {
            $cancelledStatus = OrderStatus::cancelled()->getIndex();
        } elseif ($entity === 'purchase_orders' || $entity === PurchaseOrder::class) {
            $cancelledStatus = PurchaseOrderStatus::cancelled()->getIndex();
        } else {
            $cancelledStatus = InvoiceStatus::cancelled()->getIndex();
        }
        return $cancelledStatus;
    }

    private function getEntityDraftIndex(string $entity): int
    {
        if ($entity === 'quotes' || $entity === Quote::class) {
            $draftStatus = QuoteStatus::draft()->getIndex();
        } elseif ($entity === 'orders' || $entity === Order::class) {
            $draftStatus = OrderStatus::draft()->getIndex();
        } elseif ($entity === 'purchase_orders' || $entity === PurchaseOrder::class) {
            $draftStatus = PurchaseOrderStatus::draft()->getIndex();
        } else {
            $draftStatus = InvoiceStatus::draft()->getIndex();
        }
        return $draftStatus;
    }

    private function getPurchaseOrderCostsForEarnOut(int $start, int $end, Company $company)
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
                      [
                          'bool' => [
                              'must' => [
                                  'range' => [
                                      'authorised_date' => [
                                          'gte' => $start,
                                          'lte' => $end
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

        $result = PurchaseOrder::searchBySingleQuery($query);

        return $result['aggregations']['total_price']['value'] - $result['aggregations']['total_vat']['value'];
    }

    private function getExternalSalaryCostsForEarnOut(int $start, int $end, Company $company)
    {
        if (UserRole::isAdmin(auth()->user()->role) || $company->currency_code == CurrencyCode::EUR()->getIndex()) {
            $salary = 'project_info.external_employee_cost';
        } else {
            $salary = 'project_info.external_employee_cost_usd';
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
                                      'delivered_at' => [
                                          'gte' => $start,
                                          'lte' => $end
                                      ]
                                  ],
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
              'salaries' => [
                  'nested' => ['path' => 'project_info'],
                  'aggs' => [
                      'total_salary' => [
                          'sum' => [
                              'field' => $salary
                          ]
                      ],
                  ]
              ]
          ]
        ];

        $result = Order::searchBySingleQuery($query);

        return $result['aggregations']['salaries']['total_salary']['value'];
    }

    private function getIntraCompanyInvoices($array, $company_id)
    {
        $start = $array['start'];
        $end = $array['end'];

        $statuses = [
          InvoiceStatus::authorised()->getIndex(),
          InvoiceStatus::submitted()->getIndex(),
          InvoiceStatus::paid()->getIndex(),
          InvoiceStatus::partial_paid()->getIndex()
        ];

        if (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_price = 'total_price';
            $total_vat = 'total_vat';
            $shadow_price = 'shadow_price';
            $shadow_vat = 'shadow_vat';
        } else {
            $total_price = 'total_price_usd';
            $total_vat = 'total_vat_usd';
            $shadow_price = 'shadow_price_usd';
            $shadow_vat = 'shadow_vat_usd';
        }

        $query = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => [
                      [
                          'bool' => [
                              'must' => [
                                  'terms' => ['intra_company' => [true]]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  ['terms' => ['status' => $statuses]]
                              ]
                          ]
                      ],
                  ]
              ],
          ],


          'aggs' => [
              'total_entities' => [
                  'date_range' => [
                      'field' => 'date',
                      'ranges' => [
                          ['from' => $start, 'to' => $end],
                      ],
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
              ]
          ]
        ];

        $result = Invoice::searchAllTenantsQuery('invoices', $query);
        return [
          'count' => $result['aggregations']['total_entities']['doc_count'],
          'monetary_value' => $result['aggregations']['total_entities']['monetary_value']['value']['value'],
          'vat_value' => $result['aggregations']['total_entities']['vat_value']['value']['value'],
        ];
    }
}
