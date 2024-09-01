<?php


namespace App\Repositories\Cache;

use App\Enums\CommissionModel;
use App\Enums\CommissionPercentageType;
use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Commission;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\SalesCommission;
use App\Models\SalesCommissionPercentage;
use App\Models\User;
use App\Repositories\Elastic\ElasticInvoiceRepository;
use App\Repositories\Elastic\ElasticQuoteRepository;
use App\Services\CommissionService;
use App\Services\QuoteService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Tenancy\Facades\Tenancy;

class CacheCommissionRepository extends CacheRepository
{

    private $elasticInvoiceRepository;

    private $commissionService;

    private $elasticQuoteRepository;

    public function __construct(
        ElasticInvoiceRepository $elasticInvoiceRepository,
        CommissionService $commissionService,
        ElasticQuoteRepository $elasticQuoteRepository
    ) {
        $this->elasticInvoiceRepository = $elasticInvoiceRepository;
        $this->commissionService = $commissionService;
        $this->elasticQuoteRepository = $elasticQuoteRepository;
    }

    public function all()
    {
        //
    }

    public function find($id)
    {
        //
    }


    public function allByCompany(Company $company, $timeArray)
    {
        return $this->getCompanyCommissionSummary($company, $timeArray);
    }

    public function allBySalesPersons(Company $company, array $timeArray, array $salesPersonIds)
    {

        // No catching for fimtering by sales person
        return $this->getCompanyCommissionSummary($company, $timeArray, $salesPersonIds);
    }

    private function getCompanyCommissionSummary(Company $company, $timeArray, array $filterBySalesPersonIds = [])
    {
        $customerRes = [];
        $totalCommissionForCompany = 0;
        $countCompanyCommissions = 0;
        $companyData = [
          'id' => $company->id,
          'name' => $company->name,
          'initials' => $company->initials,
          'currency' => $company->currency_code,
        ];

        Tenancy::setTenant($company);

        $invoices = $this->elasticInvoiceRepository->getCommissionInvoices($timeArray['start'], $timeArray['end'], $filterBySalesPersonIds);

        foreach ($invoices as $invoice) {
            $invoiceData = $this->formatCommissionQuoteData($invoice, $filterBySalesPersonIds, $company);

            $orderId = $invoice['_source']['order_id'];

            $customerId = $invoice['_source']['customer_id'];

            $totalCommissionAmount = isset($invoiceData['commissions']) ? $this->sumCommissionAmount($invoiceData) : 0;

            $totalCommissionForCompany += $totalCommissionAmount;

            $countCompanyCommissions += isset($invoiceData['commissions']) ? count($invoiceData['commissions']) : 0;

            if (empty($customerRes[$customerId])) {
                $customerRes[$customerId] = [
                    'id' => $customerId,
                    'name' => $invoice['_source']['customer'],
                    'quotes' => [],
                    'total_customer_commission' => 0
                ];
            }

            if (empty($customerRes[$customerId]['quotes'][$orderId])) {
                $customerRes[$customerId]['quotes'][$orderId] = [
                    'order_id' => $orderId,
                    'order' => $invoice['_source']['order'],
                    'quote_id' => $invoice['_source']['quote_id'],
                    'order_status' => $invoice['_source']['order_status'],
                    'invoices' => []
                ];
            }

            $customerRes[$customerId]['total_customer_commission'] += $totalCommissionAmount;

            $customerRes[$customerId]['quotes'][$orderId]['invoices'][] = array_merge($this->filterInvoiceData($invoice['_source']), $invoiceData);
        }


        foreach ($customerRes as $customerId => $quotes) {
            $customerRes[$customerId]['quotes'] = array_values($quotes['quotes']);
        }
        $companyData['customers'] = array_values($customerRes);
        $companyData['total_company_commission'] = $totalCommissionForCompany;
        $companyData['count_company_commissions'] = $countCompanyCommissions;
        return $companyData;
    }

    /**
     * @param array $invoiceData
     * @return float
     */
    private function sumCommissionAmount(array $invoiceData): float
    {
        $totalCommission = 0;
        foreach ($invoiceData['commissions'] as $commission) {
            $totalCommission += $commission['commission'];
        }
        return $totalCommission;
    }


    public function formatCommissionQuoteData($invoice, array $filterBySalesPersonIds, Company $company): array
    {
        $result = [];
        $invoice = $invoice['_source'];
        $result['id'] = $invoice['id'];
        $result['project_id'] = $invoice['project_info']['id'];
        $result['number'] = $invoice['number'];
        $result['order'] = $invoice['order'];
        $result['order_id'] = $invoice['order_id'];
        $result['invoiced_total_price'] = isset($invoice['total_price']) ? $invoice['total_price'] : 0;
        $result['sales_person_id'] = $invoice['sales_person_id'];
        $result['commissions'] = [];
        $invoicedTotalPaid = $invoice['total_paid_amount'];
        $selectedSalesPerson = !empty($filterBySalesPersonIds);
        $grossMargin = $invoice['markup'] ?? 0;

        $percentages = $this->commissionService->getPercentages(
            $invoice['order_id'],
            $invoice['id'],
            $filterBySalesPersonIds
        );

        foreach ($percentages as $percentage) {
            $commissions = $this->calulateCommissionsForSalesPerson(
                $company,
                $invoice,
                $percentage,
                $filterBySalesPersonIds,
                $selectedSalesPerson,
                $grossMargin,
                $invoicedTotalPaid
            );

            if (!empty($commissions)) {
                  $result['commissions'] = array_merge($result['commissions'], $commissions);
            }
        }

        if (empty($result['commissions'])) {
            return [];
        }

        return $result;
    }

    private function getDefaultSalesCommissions($invoice, $salesCommissionRecord)
    {
        $invoiceTotal = max($invoice['total_price'] - $invoice['total_vat'], 0);
        $salesArray[(string)$salesCommissionRecord->commission] = $invoiceTotal;
        return $salesArray;
    }

    private function getLeadGenarationCommissions($invoice, $salesPerson, $salesCommissionRecord)
    {
        $invoicedTotalPaid = $invoice['total_price'];
        $invoiceTotal = max($invoicedTotalPaid - $invoice['total_vat'], 0);
        $salesArray = [];

        $query = SalesCommissionPercentage::select('order_id', 'invoice_id', 'sales_person_id', 'commission_percentage', 'type', 'id', 'created_at')
        ->where('sales_person_id', $salesPerson->id)
        ->where('invoice_id', $invoice['id'])
        ->orderByDesc('created_at');
        $percentage = $query->first();
        if ($percentage) {
            $salesArray[(string)$percentage->commission_percentage] = $invoiceTotal;
        } else {
            $salesArray[0] = $invoiceTotal;
        }

        return $salesArray;
    }


    private function getLeadGenarationBCommissions($invoice, $salesPerson, $salesCommissionRecord)
    {
        $invoicedTotalPaid = $invoice['total_price'];
        $invoiceTotal = max($invoicedTotalPaid - $invoice['total_vat'], 0);
        $salesArray = [];

        $query = SalesCommissionPercentage::select('order_id', 'invoice_id', 'sales_person_id', 'commission_percentage', 'type', 'id', 'created_at')
        ->where('sales_person_id', $salesPerson->id)
        ->where('invoice_id', $invoice['id'])
        ->orderByDesc('created_at');
        $percentage = $query->first();
        if ($percentage) {
            $salesArray[(string)$percentage->commission_percentage] = $invoiceTotal;
        } else {
            $salesArray[0] = $invoiceTotal;
        }
        return $salesArray;
    }

    public function getCustomModelACommissions($company, $invoice, $filterBySalesPersonIds)
    {
        $invoicedTotalPaid = $invoice['total_price'];
        $invoiceTotal = max($invoicedTotalPaid - $invoice['total_vat'], 0);

        $revenue = $this->elasticInvoiceRepository->getCustomerRevenueUntilDate($invoice['order_paid_at'] ?? null, $invoice['customer_id'], $filterBySalesPersonIds);
        $otherQuotesRevenue = 0;

        if ($invoice['this_is_quote'] > 1) {
            $otherQuotesRevenue = $this->elasticQuoteRepository->getRevenueFromOtherQuotes($invoice);
        }

        $quoteTotalUSD = $invoice['total_price_usd'] - $invoice['total_vat_usd'];

        if ($invoice['currency_rate_company']) {
            $rate = $company->currency_code == CurrencyCode::USD()->getIndex() ?
            $invoice['currency_rate_company'] : 1 / $invoice['currency_rate_company'];
        } else {
            $rate = $company->currency_code == CurrencyCode::USD()->getIndex() ?
            (1 / getCurrencyRates()['rates']['USD']) : getCurrencyRates()['rates']['USD'];
        }

        $orderTotalPrice = ($invoicedTotalPaid - $invoice['total_vat']) * $rate;
        $revenue -= $orderTotalPrice;
        $salesArray = [];
        if ($orderTotalPrice > 500000) {
            $salesArray = $this->largeOrdersCalculation($revenue, $invoiceTotal, $quoteTotalUSD, $rate, $orderTotalPrice, $otherQuotesRevenue);
        } else {
            $revenue += $otherQuotesRevenue;
            if ($revenue <= 1000000) {
                if ($revenue + $quoteTotalUSD <= 1000000) {
                    $salesArray[10] = $invoiceTotal;
                } else {
                    $restValue = 1000000 - $revenue;
                    $quoteValueUnder = $restValue * $rate;
                    $salesArray[10] = $quoteValueUnder;
                    $quoteValueOver = $invoiceTotal - $quoteValueUnder;
                    $salesArray['7.5'] = $quoteValueOver;
                }
            } elseif ($revenue > 1000000 && $revenue <= 2000000) {
                if ($revenue + $quoteTotalUSD <= 2000000) {
                    $salesArray['7.5'] = $invoiceTotal;
                } else {
                    $restValue = 2000000 - $revenue;
                    $quoteValueUnder = $restValue * $rate;
                    $salesArray['7.5'] = $quoteValueUnder;
                    $quoteValueOver = $invoiceTotal - $quoteValueUnder;
                    $salesArray[5] = $quoteValueOver;
                }
            } else {
                $salesArray[5] = $invoiceTotal;
            }
        }
        return $salesArray;
    }

    private function calulateCommissionsForSalesPerson(
        $company,
        $invoice,
        $percentage,
        $filterBySalesPersonIds,
        bool $selectedSalesPerson,
        $grossMargin,
        $invoicedTotalPaid
    ) {
        $result = [];
        $quoteDate = date('Y-m-d', $invoice['quote_date']);
        $salesPerson = $percentage->salesPerson;
        if (!empty($filterBySalesPersonIds) && !in_array($salesPerson->id, $filterBySalesPersonIds)) {
            return [];
        }

        if (UserRole::isSales($salesPerson->role)) {
            $salesArray = [];
            if (CommissionPercentageType::isCalculated($percentage->type)) {
                $salesPerson = User::where([['id', $salesPerson->id]])->first();
                $salesCommissionRecordsCount = $salesPerson->salesCommissions->count();

                if ($salesCommissionRecordsCount) {
                    $salesCommissionRecord = $salesPerson->salesCommissions()->whereDate('created_at', '<=', $quoteDate)
                    ->orderByDesc('created_at')->first();
                    if (!$salesCommissionRecord) {
                        $salesCommissionRecord = $salesPerson->salesCommissions->sortBy('created_at')->first();
                    }
                } else {
                    return [];
                }

                if (CommissionModel::isLead_generation($salesCommissionRecord->commission_model)) {
                    $salesArray = $this->getLeadGenarationCommissions($invoice, $salesPerson, $salesCommissionRecord);
                } elseif (CommissionModel::isLead_generationB($salesCommissionRecord->commission_model)) {
                    $salesArray = $this->getLeadGenarationBCommissions($invoice, $salesPerson, $salesCommissionRecord);
                } elseif (CommissionModel::isCustom_modelA($salesCommissionRecord->commission_model)) {
                    $salesArray = $this->getCustomModelACommissions($company, $invoice, $filterBySalesPersonIds);
                } elseif (CommissionModel::isSales_support($salesCommissionRecord->commission_model)) {
                    $salesArray = $this->getSalesSupportCommissions($company, $invoice, $salesCommissionRecord, $selectedSalesPerson);
                } else {
                    $salesArray = $this->getDefaultSalesCommissions($invoice, $salesCommissionRecord);
                }
            } else {
                if (!CommissionPercentageType::isCalculated($percentage->type)) {
                    $invoiceTotal = max($invoicedTotalPaid - $invoice['total_vat'], 0);
                    $salesArray["$percentage->commission_percentage"] = $invoiceTotal;
                }
            }

            foreach ($salesArray as $key => $value) {
                $commission = $this->formatCommission(
                    $invoice,
                    $grossMargin,
                    $invoicedTotalPaid,
                    $percentage,
                    $key,
                    $value
                );

                if (!empty($commission)) {
                      $result[] = $commission;
                }
            }
        }
        return $result;
    }

    private function getSalesSupportCommissions($company, $invoice, $salesCommissionRecord, $selectedSalesPerson)
    {
        $invoicedTotalPaid = $invoice['total_price'];
        $invoiceTotal = max($invoicedTotalPaid - $invoice['total_vat'], 0);
        $quoteDate = date('Y-m-d', $invoice['quote_date']);
        $salesArray = [];
        if ($selectedSalesPerson) {
            $salesPersonIds = User::where([['company_id', $company->id], ['role', UserRole::sales()->getIndex()]])->pluck('id')->toArray();
            $salesSupports = SalesCommission::select('sales_person_id')
            ->whereIn('sales_person_id', $salesPersonIds)
            ->where(function ($query) use ($quoteDate) {
                $query->where('commission_model', CommissionModel::sales_support()->getIndex())
                    ->whereDate('created_at', '<=', $quoteDate);
            })
              ->groupBy('sales_person_id')
              ->get();

            $commissionPercent = $salesCommissionRecord->commission / ($salesSupports->isEmpty() ? 1 : $salesSupports->count());
            $salesArray[(string)$commissionPercent] = $invoiceTotal;
        } else {
            $salesArray[(string)$salesCommissionRecord->commission] = $invoiceTotal;
        }

        return $salesArray;
    }

    private function formatCommission($invoice, $grossMargin, $invoicedTotalPaid, $percentage, $key, $value)
    {
        if (CommissionPercentageType::isCalculated($percentage->type)) {
            $commissionPercentage = QuoteService::getCommissionPercentage($key, $grossMargin);
            $commissionAmount = QuoteService::calculateCommission($commissionPercentage, $value);
        } else {
            $commissionPercentage = $percentage->commission_percentage;
            $commissionAmount = QuoteService::calculateCommission($commissionPercentage, $invoicedTotalPaid);
        }
        $percentageSalesPerson = User::find($percentage->sales_person_id);
        $payValueAt = Commission::getPaidValue($percentage->sales_person_id, $invoice['order_id'], $invoice['id']);
        $commission['commission_percentage_id'] = $percentage->id;
        $commission['gross_margin'] = $grossMargin;
        $commission['sales_person_commission'] = $key;
        $commission['commission_percentage'] = $commissionPercentage;
        $commission['commission'] = $commissionAmount;
        $commission['sales_person'] = $percentageSalesPerson->name;
        $commission['sales_person_id'] = $percentage->sales_person_id;
        $commission['total'] = round($value, 2);
        $commission['total_price'] = round($invoice['total_price'], 2);
        $commission['showActions'] = false;
        $commission['paid_value'] = $payValueAt['paid_value'];
        $commission['paid_at'] = $payValueAt['paid_at'];
        $commission['status'] = Commission::getStatus($commission['paid_value'], $commission['commission']);

        return $commission;
    }

    private function getBaseCommission($salespersonId): float
    {
        $baseCommission = SalesCommission::DEFAULT_COMMISSION;

        if ($salespersonId) {
            $salesPerson = User::where('id', $salespersonId)->firstOrFail();
            if (!$salesPerson->primary_account) {
                $salesPerson = User::where([['email', $salesPerson->email], ['primary_account', true]])->first();
            }
            $baseCommission = $salesPerson->salesCommissions->sortByDesc('created_at')->first()->commission;
        }
        return $baseCommission;
    }


    public function largeOrdersCalculation($revenue, $quoteTotal, $quoteTotalUSD, $rate, $orderTotalPrice, $otherQuotesRevenue): array
    {
        $salesArray = [];

        if ($revenue + $orderTotalPrice <= 1000000) {
            if ($otherQuotesRevenue == 0) {
                if ($quoteTotalUSD - 500000 > 0) {
                    $quoteLarge = 500000 * $rate;
                    $quoteRest = ($quoteTotalUSD - 500000) * $rate;
                    $salesArray[10] = $quoteLarge;
                    $salesArray[5] = $quoteRest;
                } else {
                    $quoteLarge = (500000 - $quoteTotalUSD) * $rate;
                    $salesArray[10] = $quoteLarge;
                }
            } else {
                if ($otherQuotesRevenue >= 500000) {
                    $salesArray[5] = $quoteTotal;
                } else {
                    $restValue = 500000 - $otherQuotesRevenue;
                    if ($quoteTotalUSD > $restValue) {
                        $quoteLarge = $restValue * $rate;
                        $quoteRest = $quoteTotal - $quoteLarge;
                        $salesArray[10] = $quoteLarge;
                        $salesArray[5] = $quoteRest;
                    } else {
                        $salesArray[10] = $quoteTotal;
                    }
                }
            }
        } elseif ($revenue + $orderTotalPrice > 1000000 && $revenue + $orderTotalPrice <= 2000000) {
            if ($revenue <= 1000000) {
                $restValue = 1000000 - $revenue;
                if ($otherQuotesRevenue == 0) {
                    if ($quoteTotalUSD <= $restValue) {
                        $salesArray[10] = $quoteTotal;
                    } else {
                        if ($restValue > 500000) {
                            $quoteLarge = 500000 * $rate;
                            $quoteRest = $quoteTotal - $quoteLarge;
                            $salesArray[10] = $quoteLarge;
                            $salesArray[5] = $quoteRest;
                        } else {
                            $quoteLarge = $restValue * $rate;
                            $salesArray[10] = $quoteLarge;
                            $restAbove = 500000 - $restValue;
                            if ($quoteTotalUSD - $restValue < $restAbove) {
                                $quoteMiddle = ($quoteTotalUSD - $restValue) * $rate;
                                $salesArray['7.5'] = $quoteMiddle;
                            } else {
                                $quoteMiddle = $restAbove * $rate;
                                $salesArray['7.5'] = $quoteMiddle;
                                $quoteRest = $quoteTotal - $quoteLarge - $quoteMiddle;
                                $salesArray[5] = $quoteRest;
                            }
                        }
                    }
                } else {
                    if ($otherQuotesRevenue >= 500000) {
                        $salesArray[5] = $quoteTotal;
                    } else {
                        $restLargeOrder = 500000 - $otherQuotesRevenue;
                        if ($quoteTotalUSD > $restLargeOrder) {
                            $quoteLarge = $restLargeOrder * $rate;
                            $salesArray[$revenue + $otherQuotesRevenue >= 1000000 ? 10 : '7.5'] = $quoteLarge;
                            $quoteRest = $quoteTotal - $quoteLarge;
                            $salesArray[5] = $quoteRest;
                        } else {
                            $quoteLarge = $restLargeOrder * $rate;
                            $salesArray[$revenue + $otherQuotesRevenue >= 1000000 ? 10 : '7.5'] = $quoteLarge;
                        }
                    }
                }
            } elseif ($revenue > 1000000) {
                if ($otherQuotesRevenue == 0) {
                    if ($quoteTotalUSD <= 500000) {
                        $salesArray['7.5'] = $quoteTotal;
                    } else {
                        $quoteLarge = 500000 * $rate;
                        $quoteRest = $quoteTotalUSD - 500000 * $rate;
                        $salesArray['7.5'] = $quoteLarge;
                        $salesArray[5] = $quoteRest;
                    }
                } else {
                    if ($otherQuotesRevenue >= 500000) {
                        $salesArray[5] = $quoteTotal;
                    } else {
                        $restLargeOrder = 500000 - $otherQuotesRevenue;
                        if ($quoteTotalUSD > $restLargeOrder) {
                            $quoteLarge = $restLargeOrder * $rate;
                            $salesArray['7.5'] = $quoteLarge;
                            $quoteRest = $quoteTotal - $quoteLarge;
                            $salesArray[5] = $quoteRest;
                        } else {
                            $quoteLarge = $restLargeOrder * $rate;
                            $salesArray['7.5'] = $quoteLarge;
                        }
                    }
                }
            }
        } else {
            if ($revenue < 1000000) {
                $restValue = 1000000 - $revenue;
                if ($otherQuotesRevenue == 0) {
                    if ($quoteTotalUSD <= $restValue) {
                        $salesArray[10] = $quoteTotal;
                    } else {
                        if ($restValue > 500000) {
                            $quoteLarge = 500000 * $rate;
                        } else {
                            $quoteLarge = $restValue * $rate;
                        }
                        $quoteRest = $quoteTotal - $quoteLarge;
                        $salesArray[10] = $quoteLarge;
                        $salesArray[5] = $quoteRest;
                    }
                } else {
                    if ($otherQuotesRevenue >= 500000 || $revenue + $otherQuotesRevenue >= 2000000) {
                        $salesArray[5] = $quoteTotal;
                    } else {
                        $restLargeOrder = 500000 - $otherQuotesRevenue;
                        if ($quoteTotalUSD > $restLargeOrder) {
                            $quoteLarge = $restLargeOrder * $rate;
                            $salesArray[10] = $quoteLarge;
                            $quoteRest = $quoteTotal - $quoteLarge;
                            $salesArray[5] = $quoteRest;
                        } else {
                            $quoteLarge = $restLargeOrder * $rate;
                            $salesArray[10] = $quoteLarge;
                        }
                    }
                }
            } elseif ($revenue < 2000000) {
                $restValue = 2000000 - $revenue;
                if ($otherQuotesRevenue == 0) {
                    if ($quoteTotalUSD <= $restValue) {
                        $salesArray['7.5'] = $quoteTotal;
                    } else {
                        if ($restValue > 500000) {
                            $quoteLarge = 500000 * $rate;
                        } else {
                            $quoteLarge = $restValue * $rate;
                        }
                        $quoteRest = $quoteTotal - $quoteLarge;
                        $salesArray['7.5'] = $quoteLarge;
                        $salesArray[5] = $quoteRest;
                    }
                } else {
                    if ($otherQuotesRevenue >= 500000 || $revenue + $otherQuotesRevenue >= 2000000) {
                        $salesArray[5] = $quoteTotal;
                    } else {
                        $restLargeOrder = 500000 - $otherQuotesRevenue;
                        if ($quoteTotalUSD > $restLargeOrder) {
                            $quoteLarge = $restLargeOrder * $rate;
                            $salesArray['7.5'] = $quoteLarge;
                            $quoteRest = $quoteTotal - $quoteLarge;
                            $salesArray[5] = $quoteRest;
                        } else {
                            $quoteLarge = $restLargeOrder * $rate;
                            $salesArray['7.5'] = $quoteLarge;
                        }
                    }
                }
            } else {
                $salesArray[5] = $quoteTotal;
            }
        }

        return $salesArray;
    }

    private function filterInvoiceData($invoiceData)
    {
        $includes = [
          'id', 'number'
        ];
        return  Arr::only($invoiceData, $includes);
    }
}
