<?php

namespace App\Services;


use App\Enums\CommissionModel;
use App\Enums\CommissionPaymentLogStatus;
use App\Enums\CommissionPercentageType;
use App\Enums\CurrencyCode;
use App\Enums\PaymentLogStatus;
use App\Enums\UserRole;
use App\Http\Resources\Commission\CommissionPaymentLogResource;
use App\Mail\CommissionPaymentLogNotification;
use App\Mail\UnpaidCommissionPaymentLogNotification;
use App\Models\Commission;
use App\Models\CommissionPaymentLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Project;
use App\Models\SalesCommission;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use App\Models\SalesCommissionPercentage;
use App\Repositories\Cache\CacheCommissionRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tenancy\Facades\Tenancy;

/**
 * Class CommissionService
 */
class CommissionService
{
    protected CommissionPaymentLog $commissionPaymentLog;

    public function __construct(CommissionPaymentLog $commissionPaymentLog)
    {
        $this->commissionPaymentLog = $commissionPaymentLog;
    }

    public function getPaymentLog($attributes)
    {
        $salespersonId = $attributes['sales_person_id'];
        $salespersonIds = getAllSalespersonIds($salespersonId);
        $commissionPaymentLogs = CommissionPaymentLog::whereIn('sales_person_id', $salespersonIds)->orderBy('created_at', 'desc')->get();
        return $commissionPaymentLogs;
    }

    public function createCommissionPaymentLog($attributes)
    {
        $newCommissionPaymentLog = $this->commissionPaymentLog->create($attributes);
        if ($newCommissionPaymentLog) {
            try {
                $salesPerson = User::findOrFail($attributes['sales_person_id']);
                $amount = $attributes['amount'];
                Mail::to($salesPerson->email)
                    ->queue(new CommissionPaymentLogNotification($salesPerson, $amount));
            } catch (\Exception $exception) {
            }
        }

        return $newCommissionPaymentLog;
    }

    public function updateCommissionPaymentLog($paymentLogId, $attributes)
    {
        $commissionPaymentLog = CommissionPaymentLog::findOrFail($paymentLogId);
        $commissionPaymentLog = tap($commissionPaymentLog)->update($attributes);
        return $commissionPaymentLog;
    }

    public static function getCommissionTotalOpenAmount($salespersonId, $day, $week, $month, $quarter, $year)
    {
        $salespersonIds = getAllSalespersonIds($salespersonId);

        $totalPaidCommissions = CommissionPaymentLog::whereIn('sales_person_id', $salespersonIds)->sum('amount');

        /**
         * @var AnalyticService
         */
        $analyticService = App::make(AnalyticService::class);
        $commissionSummary = $analyticService->commissionSummary($salespersonId, $day, $week, $month, $quarter, $year, true);

        return ($commissionSummary['total_all_companies_commission'] ?? 0) - $totalPaidCommissions;
    }

    public function addLeadGenerationCustomer(?array $salesPersons, string $customerId, string $projectId, string $invoiceId, object $date)
    {
        if ($salesPersons) {
            foreach ($salesPersons as $salesPerson) {
                $salesPerson = User::where([['id', $salesPerson->id],['role', UserRole::sales()->getIndex()]])->first();
                if (!$salesPerson) {
                    continue;
                }
                $commissionModel = $salesPerson->salesCommissions->sortByDesc('created_at')->first()->commission_model ?? null;
                if ($commissionModel && CommissionModel::isLead_generation($commissionModel)) {
                    $salesPerson->customerSales()->attach($customerId, ['project_id' => $projectId, 'invoice_id' => $invoiceId, 'pay_date' => $date]);
                }
                if ($commissionModel && CommissionModel::isLead_generationB($commissionModel)) {
                    $salesPerson->customerSales()->attach($customerId, ['project_id' => $projectId, 'invoice_id' => $invoiceId, 'pay_date' => $date]);
                }
            }
        }
    }

    public function removeLeadGenerationCustomer(string $customerId, string $projectId, string $invoiceId): void
    {

        $customer = Customer::find($customerId);
        if (!empty($customer)) {
            $customer->leadGenerationSales()
            ->wherePivot('project_id', $projectId)
            ->wherePivot('invoice_id', $invoiceId)
            ->detach();
        }
    }


    public function addSingleLeadGenerationCustomer(?string $salesPersonId, string $customerId, string $projectId, string $invoiceId, object $date)
    {
        if ($salesPersonId) {
            $salesPerson = User::where('id', $salesPersonId)
                ->where('role', UserRole::sales()->getIndex())
                ->first();

            if ($salesPerson) {
                $latestCommissionModel = $salesPerson->salesCommissions->sortByDesc('created_at')->first()->commission_model ?? null;
                if (
                    $latestCommissionModel &&
                    (CommissionModel::isLead_generation($latestCommissionModel) ||
                        CommissionModel::isLead_generationB($latestCommissionModel))
                ) {
                    $salesPerson->customerSales()->attach($customerId, [
                        'project_id' => $projectId,
                        'invoice_id' => $invoiceId,
                        'pay_date' => $date
                    ]);
                }
            }
        }
    }


    public function createCommissionPercentage(string $orderId, string $invoiceId, $salesPersonId, $data)
    {

        $existingRecord = SalesCommissionPercentage::where('order_id', $orderId)
            ->where('invoice_id', $invoiceId)
            ->where('sales_person_id', $salesPersonId)
            ->first();
        if ($existingRecord) {
            return $existingRecord;
        }
        $salesPerson = User::where('id', $salesPersonId)
            ->where('role', UserRole::sales()->getIndex())
            ->first();
        if (!$salesPerson) {
            return;
        }
        $this->setCurrentUserTenant($salesPerson);
        $invoice = Invoice::where('id', $invoiceId)->first();
        if (!$invoice) {
            return;
        }
        $project = $invoice->project;

        $salesPersonIds = collect($project->salesPersons->pluck('id'));
        $leadGensIds = collect($project->leadGens->pluck('id'));
        $uniqueIds = $salesPersonIds->merge($leadGensIds)->unique();

        if (!in_array($salesPersonId, $uniqueIds->toArray())) {
            $project->salesPersons()->attach($salesPersonId);
            $this->addSingleLeadGenerationCustomer(
                $salesPersonId,
                $project->contact->customer_id,
                $project->id,
                $invoiceId,
                $invoice->pay_date
            );
        }

        $existingRecord = SalesCommissionPercentage::where('order_id', $orderId)
            ->where('invoice_id', $invoiceId)
            ->where('sales_person_id', $salesPersonId)
            ->first();
        if ($existingRecord) {
            return $existingRecord->update(array_merge(['commission_percentage' => $data['commission_percentage']]));
        }

        return SalesCommissionPercentage::create(array_merge($data, [
            'order_id' => $orderId,
            'invoice_id' => $invoiceId,
            'sales_person_id' => $salesPersonId
        ]));
    }


    /**
     * Add commission percentage to sales person and second sales person
     * This function is call when invoice is created
     */
    public function createCommissionPercentagesFromInvoice(Invoice $invoice)
    {
        if ($invoice->project) {
            $projectId = $invoice->project->id;
            $project = Project::find($projectId);


            $salesPersons = $project->salesPersons ?? collect();
            $leadGens = $project->leadGens ?? collect();

            if (!is_iterable($salesPersons) || !is_iterable($leadGens)) {
                throw new \Exception('Expected iterable for salesPersons and leadGens');
            }

            $salesPeoples = collect();

            foreach ($salesPersons as $salesPerson) {
                if ($salesPerson && !$salesPeoples->contains('id', $salesPerson->id)) {
                    $salesPeoples->push($salesPerson);
                }
            }

            foreach ($leadGens as $leadGen) {
                if ($leadGen && !$salesPeoples->contains('id', $leadGen->id)) {
                    $salesPeoples->push($leadGen);
                }
            }

            $salesPeoples = $salesPeoples->filter()->unique('id')->sort(function ($a, $b) {
                $aIsLeadGenB = $this->isLeadGenB($a);
                $bIsLeadGenB = $this->isLeadGenB($b);

                if ($aIsLeadGenB && !$bIsLeadGenB) {
                    return 1;
                } elseif (!$aIsLeadGenB && $bIsLeadGenB) {
                    return -1;
                } else {
                    return 0;
                }
            });
            $salesPeoples = collect($salesPeoples)->unique('id')->values()->all();
            $this->createCommissionPercentagesForSalesPerson($invoice, $salesPeoples);
        }
    }


    /**
     * The commission percentage is added to a sales person for a given order.
     *
     * @param  Invoice $invoice
     * @param  mixed $salesPerson
     * @param  boolean $leadGen
     * @return void
     */
    public function createCommissionPercentagesForSalesPerson($invoice, $salesPersons)
    {
        if (!empty($salesPersons)) {
            $leadGenBCount = 0;
            $leadGenCount = 0;
            $otherSalesPersons = [];
            $leadGenBPersons = [];
            $customModelAPersonsCount = 0;

            foreach ($salesPersons as $salesPerson) {
                $salesPerson = User::find($salesPerson->id);
                if (!$salesPerson) {
                    continue;
                }
                $linkedUsers = User::where('email', $salesPerson->email)->orderBy('created_at', 'ASC')->get();
                $salesPerson = $linkedUsers->where('primary_account', true)->first() ?? $salesPerson;
                $salesCommissionRecord = $salesPerson->salesCommissions->sortByDesc('created_at')->first();
                if ($salesCommissionRecord) {
                    if (CommissionModel::isLead_generationB($salesCommissionRecord->commission_model)) {
                        $leadGenBCount++;
                        $leadGenBPersons[] = $salesPerson;
                    } elseif (CommissionModel::isLead_generation($salesCommissionRecord->commission_model)) {
                        $leadGenCount++;
                    } elseif (CommissionModel::isCustom_modelA($salesCommissionRecord->commission_model)) {
                        $customModelAPersonsCount++;
                    } else {
                        $otherSalesPersons[] = $salesPerson;
                    }
                }
            }
            $counterArray= $this->calculateTotalCommissionLeft($invoice->project->contact->customer->id, $leadGenBPersons);
            $leadGenBCount = $counterArray['leadGenBCount'];
            $leadGenBTotalLeftCom = $counterArray['totalCommissionLeft'];
            foreach ($salesPersons as $salesPerson) {
                $salesPerson = $salesPerson = User::find($salesPerson->id);
                if (!$salesPerson) {
                    continue;
                }
                $linkedUsers = User::where('email', $salesPerson->email)->orderBy('created_at', 'ASC')->get();
                $salesPerson = $linkedUsers->where('primary_account', true)->first() ?? $salesPerson;
                $salesCommissionRecordsCount = $salesPerson->salesCommissions->count();
                $order = $invoice->order;
                if (!empty($order->quote->date)) {
                    $quoteDate = $order->quote->date->format('Y-m-d');
                    if ($salesCommissionRecordsCount) {
                        $salesCommissionRecord = $salesPerson->salesCommissions()->whereDate('created_at', '<=', $quoteDate)
                            ->orderByDesc('created_at')->first();
                        if (!$salesCommissionRecord) {
                            $salesCommissionRecord = $salesPerson->salesCommissions->sortBy('created_at')->first();
                        }

                        $commissionPercentage = 0;
                        $commission = $this->getPercentagesForSalesPerson($invoice->order->id, $invoice->id, $salesPerson->id);
                        if (CommissionModel::isLead_generation($salesCommissionRecord->commission_model)) {
                            $customerId = $invoice->project->contact->customer->id;
                            $sales = $salesPerson->customerSales()->where('customer_id', $customerId)
                                ->orderBy('pay_date')->get();
                            if ($sales->count() == 0) {
                                // If sales person has no sales
                                $commissionPercentage = $salesCommissionRecord->commission;
                            } elseif ($sales->count() == 1) {
                                // If sales person has only one sale
                                $commissionPercentage = $salesCommissionRecord->second_sale_commission;
                            }
                        } elseif (CommissionModel::isCustom_modelA($salesCommissionRecord->commission_model)) {
                            $cacheCommissionRepository = App::make(CacheCommissionRepository::class);
                            $invoiceElastic = $this->getInvoiceElastic($invoice->id);
                            $company = Company::find(getTenantWithConnection());
                            $filterBySalesPersonIds[] = $salesPerson->id;
                            $salesCom = $cacheCommissionRepository->getCustomModelACommissions($company, $invoiceElastic, $filterBySalesPersonIds);
                            $commissionPercentage = key($salesCom);
                        } else {
                            if ($leadGenBCount < 1) {
                                if (!CommissionModel::isLead_generationB($salesCommissionRecord->commission_model)) {
                                    if (count($otherSalesPersons) == 1) {
                                        $commissionPercentage = $salesCommissionRecord->commission;
                                    } else {
                                        $commissionPercentage = safeDivide(3, count($otherSalesPersons));
                                    }
                                }
                            } elseif ($leadGenBCount < 3) {
                                if (CommissionModel::isLead_generationB($salesCommissionRecord->commission_model)) {
                                    $customerId = $invoice->project->contact->customer->id;
                                    $sales = $salesPerson->customerSales()->where('customer_id', $customerId)
                                        ->orderBy('pay_date')->get();
                                    if ($sales->count() < 3) {
                                        $commissionPercentage = $salesCommissionRecord->commission;
                                        $leadGenBTotalLeftCom--;
                                    }
                                } else {
                                    $commissionPercentage = $leadGenBTotalLeftCom <= 0 ? safeDivide(3, count($otherSalesPersons)) : safeDivide(3 - $leadGenBCount, count($otherSalesPersons));
                                }
                            } else {
                                if (CommissionModel::isLead_generationB($salesCommissionRecord->commission_model)) {
                                    $customerId = $invoice->project->contact->customer->id;
                                    $sales = $salesPerson->customerSales()->where('customer_id', $customerId)
                                        ->orderBy('pay_date')->get();
                                    if ($sales->count() < 3) {
                                        $commissionPercentage = safeDivide(3, count($otherSalesPersons) + $leadGenBCount);
                                        $leadGenBTotalLeftCom--;
                                    }
                                } else {
                                    $commissionPercentage = $leadGenBTotalLeftCom <= 0 ? safeDivide(3, count($otherSalesPersons)) : safeDivide(3, count($otherSalesPersons) + $leadGenBCount);
                                }
                            }
                        }

                        if ($commissionPercentage && !empty($commission) && CommissionPercentageType::isCalculated($commission->type)) {
                            $commission->update(['commission_percentage' => $commissionPercentage]);
                        } elseif ($commissionPercentage && empty($commission)) {
                            $this->createCommissionPercentage(
                                $order->id,
                                $invoice->id,
                                $salesPerson->id,
                                ['commission_percentage' => $commissionPercentage, 'type' => CommissionPercentageType::calculated()->getIndex()]
                            );
                        }
                    }
                }
            }
        }
    }


    /**
     * Update sales commissions percentage for a sales person
     * @param string $orderId
     * @param string $invoiceId,
     * @param string $salesPersonId,
     * @param array $data
     * @return SalesCommissionPercentage
     * @throws ModelNotFoundException
     */
    public function updateCommissionPercentage(string $orderId, string $invoiceId, $salesPersonId, $data)
    {
        /**
         * @var SalesCommissionPercentage
         */
        $commissionPercentage = SalesCommissionPercentage::where(
            ['order_id' => $orderId, 'invoice_id' => $invoiceId, 'sales_person_id' => $salesPersonId]
        )->firstOrFail();

        if (CommissionPercentageType::isCalculated($commissionPercentage->type)) {
            $data['type'] = CommissionPercentageType::uncalculated()->getIndex();
        }

        $commissionPercentage->update($data);

        return $commissionPercentage;
    }


    /**
     * getPercentages
     *
     * @param  string $orderId
     * @param  array $filterSalesPersonIds
     * @return Collection
     */
    public function getPercentages(string $orderId, string $invoiceId, array $filterSalesPersonIds = []): Collection
    {
        $query = SalesCommissionPercentage::select('order_id', 'invoice_id', 'sales_person_id', 'commission_percentage', 'type', 'id')
            ->where('order_id', $orderId)
            ->where('invoice_id', $invoiceId)
            ->orderByDesc('type');
        if (!empty($filterSalesPersonIds)) {
            $query->whereIn('sales_person_id', $filterSalesPersonIds);
        }
        return $query->get();
    }

    /**
     * getPercentagesWithCommissionType
     *
     * @param  string $orderId
     * @param  array $filterSalesPersonIds
     * @return Collection
     */
    public function getPercentagesWithCommissionType(string $orderId, $filterSalesPersonIds = [], int $type): Collection
    {
        $query = SalesCommissionPercentage::select('order_id', 'sales_person_id', 'commission_percentage', 'id', 'type')
            ->where('order_id', $orderId)
            ->where('type', $type);
        if (!empty($filterSalesPersonIds)) {
            $query->whereIn('sales_person_id', $filterSalesPersonIds);
        }
        return $query->get();
    }

    public function deleteCommissionPercentage(string $orderId, string $invoiceId, string $salesPersonId)
    {
        $percentage = SalesCommissionPercentage::where([
            'order_id' => $orderId,
            'invoice_id' => $invoiceId,
            'sales_person_id' => $salesPersonId
        ])->first();

        if ($percentage) {
            return $percentage->delete();
        }

        return null;
    }

    /**
     * Remove all commissions for the given invoice
     * @return void
     */
    public function removeCommissionPercentagesForInvoice(Invoice $invoice): void
    {

        SalesCommissionPercentage::where([
            'invoice_id' => $invoice->invoiceId
        ])->delete();
    }

    public function deleteCommissionPercentageById($percentageId)
    {

        $percentage = SalesCommissionPercentage::find($percentageId);
        if ($percentage) {
            return $percentage->delete();
        }
        return null;
    }

    /**
     * getPercentagesByCommissionType
     *
     * @param  string $orderId
     * @param  string $salesPersonId
     * @param  CommissionPercentageType $type
     * @return mixed
     */
    public function getPercentagesForSalesPerson(string $orderId, string $invoiceId, string $salesPersonId)
    {
        return SalesCommissionPercentage::select('order_id', 'invoice_id', 'sales_person_id', 'commission_percentage', 'type')
            ->where('order_id', $orderId)
            ->where('invoice_id', $invoiceId)
            ->where('sales_person_id', $salesPersonId)
            ->first();
    }

    /**
     * getPercentagesByCommissionType
     *
     * @param  string $orderId
     * @param  string $salesPersonId
     * @param  CommissionPercentageType $type
     * @return mixed
     */
    public function getPercentagesByCommissionType(string $orderId, string $salesPersonId, CommissionPercentageType $type)
    {
        return SalesCommissionPercentage::select('order_id', 'sales_person_id', 'commission_percentage', 'type')
            ->where('order_id', $orderId)
            ->where('sales_person_id', $salesPersonId)
            ->where('type', $type->getIndex())
            ->first();
    }

    public function getAllPercentagesByCommissionType(string $orderId, string $salesPersonId, CommissionPercentageType $type)
    {
        return SalesCommissionPercentage::select('order_id', 'sales_person_id', 'commission_percentage', 'type')
            ->where('order_id', $orderId)
            ->where('sales_person_id', $salesPersonId)
            ->where('type', $type->getIndex())
            ->get();
    }

    public function createIndividualCommissionPaymentFormRequest($data)
    {
        try {
            // Verify if the commission is already paid
            $commission = Commission::where('sales_person_id', $data['sales_person_id'])
                ->where('order_id', $data['order_id'])
                ->where('invoice_id', $data['invoice_id'])
                ->first();

            if ($commission) {
                // If the commission already exists
                if (($commission->paid_value + $data['amount']) > $commission->total) {
                    return false;
                }
                $commission->paid_value += $data['amount'];
                $commission->update();
            } else {
                // Register the commission payment
                $commission = Commission::create([
                    'sales_person_id' => $data['sales_person_id'],
                    'order_id' => $data['order_id'],
                    'invoice_id' => $data['invoice_id'],
                    'paid_value' => $data['amount'],
                    'total' => $data['total'],
                ]);
            }

            // Log the Payment
            $this->commissionPaymentLog->create([
                'sales_person_id' => $data['sales_person_id'],
                'amount' => $data['amount'],
                'approved' => true,
            ]);

            // Notify the sales person
            $salesPerson = User::findOrFail($data['sales_person_id']);
            $amount = $data['amount'];
            Mail::to($salesPerson->email)->queue(new CommissionPaymentLogNotification($salesPerson, $amount));

            return $commission;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Cancelling individual commission payment on a sales person according to an order
     * @param string $orderId
     * @param string $salesPersonId
     */
    public function cancelIndividualCommissionPaymentFormRequest(string $orderId, string $invoiceId, string $salesPersonId)
    {
        $commissions = Commission::where('sales_person_id', $salesPersonId)
            ->where('order_id', $orderId)
            ->where('invoice_id', $invoiceId);

        $totalPaidAmount = $commissions->get()->sum('paid_value');

        if ($totalPaidAmount > 0) {
            // Log the Payment
            $this->commissionPaymentLog->create([
                'sales_person_id' => $salesPersonId,
                'amount' => -$totalPaidAmount,
                'approved' => CommissionPaymentLogStatus::canceled()->getIndex(),
            ]);

            // Notify the sales person
            $salesPerson = User::findOrFail($salesPersonId);
            Mail::to($salesPerson->email)->queue(new UnpaidCommissionPaymentLogNotification($salesPerson, $totalPaidAmount));

            return $commissions->delete();
        }
        return null;
    }

    public function getInvoiceElastic($invoiceID)
    {
        if (empty($invoiceID)) {
            throw new InvalidArgumentException('Invalid invoice ID provided.');
        }

        $query = [
            'bool' => [
                'must' => [
                    ['match' => ['id' => $invoiceID]]
                ]
            ]
        ];

        $elasticInvoice = Invoice::searchByQuery($query, null, [], []);

        if (empty($elasticInvoice->getHits()['hits'])) {
            throw new RuntimeException("Invoice not found for ID: $invoiceID");
        }

        return $elasticInvoice->getHits()['hits'][0]['_source'];
    }

    public function setCurrentUserTenant($user)
    {
        $company = Company::find($user->company_id);
        Tenancy::setTenant($company);
    }

    public function calculateTotalCommissionLeft($customerId, $salesPersonLeadgenbs)
    {
        $totalCommissionLeft = 0;
        $leadGenBCount = 0;

        foreach ($salesPersonLeadgenbs as $salesPerson) {
            $salesCount = $salesPerson->customerSales()
                ->where('customer_id', $customerId)
                ->orderBy('pay_date')
                ->count();
            if ($salesCount < 3) {
                $totalCommissionLeft += (3 - $salesCount);
                $leadGenBCount++;
            }
        }

        return [
            'leadGenBCount' => $leadGenBCount,
            'totalCommissionLeft' => $totalCommissionLeft
        ];
    }

    public function isLeadGenB($salesPerson)
    {
        $salesCommissionRecord = $salesPerson->salesCommissions()
            ->whereDate('created_at', '<=', now())
            ->orderByDesc('created_at')
            ->first();

        if (!$salesCommissionRecord) {
            $salesCommissionRecord = $salesPerson->salesCommissions->sortBy('created_at')->first();
        }

        return $salesCommissionRecord && CommissionModel::isLead_generationB($salesCommissionRecord->commission_model);
    }
}
