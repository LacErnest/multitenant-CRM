<?php

namespace App\Observers;

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Jobs\XeroUpdate;
use App\Mail\InvoiceAuthorised;
use App\Mail\InvoiceNotAuthorised;
use App\Mail\PurchaseOrderPaid;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Resource;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\CompanyLoanService;
use App\Services\EmployeeService;
use App\Services\ItemService;
use App\Services\ResourceService;
use App\Services\Xero\Auth\AuthService as XeroAuthService;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tenancy\Facades\Tenancy;

class InvoiceObserver
{
    public function saving(Invoice $invoice)
    {
        if ($invoice->exists && InvoiceStatus::isPaid($invoice->status) && InvoiceType::isAccrec($invoice->type)&& $invoice->project && $invoice->project->contact) {
            $days = $invoice->date->diff($invoice->pay_date)->days;
            $customer = $invoice->project->contact->customer()->first();
            $query = [
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
                                    ['match' => ['status' => InvoiceStatus::paid()->getIndex()]]
                                ]
                            ]
                        ],
                        [
                            'bool' => [
                                'must' => [
                                    ['match' => ['customer_id' => $customer->id]]
                                ]
                            ]
                        ],
                    ]
                ]
            ]
            ];
            $params = array(
            'index' => '*_invoices',
            'type' => '_doc',
            'body' => $query
            );
            $result = (new Invoice)->getElasticSearchClient()->count($params);
            $paidInvoices = $result['count'] ?? 0;
            $formerDays = $customer->average_collection_period * ($paidInvoices);
            $newDays = ($formerDays + $days) / ($paidInvoices + 1);
            $customer->average_collection_period = $newDays;
            $customer->save();
        }
    }

    /*public function created(Invoice $invoice)
    {
        // When created, we just add commision percentages to sales person and second sales person
        $commissionService = App::make(CommissionService::class);
        $commissionService->createCommissionPercentagesFromInvoice($invoice);
    }*/

    public function updated(Invoice $invoice)
    {
        $companyId = getTenantWithConnection();

        if ($invoice->isDirty('status')) {
            if (InvoiceType::isAccrec($invoice->type)) {
                if (InvoiceStatus::isApproval($invoice->status)) {
                    $company = Tenancy::getTenant();
                    Mail::to($company->users()->where('role', UserRole::owner()->getIndex())->get())->queue(new InvoiceNotAuthorised($company->id, $invoice->id));
                } elseif (InvoiceStatus::isAuthorised($invoice->status)) {
                    if ($invoice->creator()->exists()) {
                        Mail::to($invoice->creator)->queue(new InvoiceAuthorised(getTenantWithConnection(), $invoice->id));
                    }
                }

                if (InvoiceStatus::isPaid($invoice->status)) {
                    $order = $invoice->order;
                    if ($order) {
                        if (OrderStatus::isActive($order->status) || OrderStatus::isDelivered($order->status) || OrderStatus::isInvoiced($order->status)) {
                                $invoices = $invoice->order->invoices()->where([
                                ['type', InvoiceType::accrec()->getIndex()],
                                ['status', '!=', InvoiceStatus::unpaid()->getIndex()],
                                ['status', '!=', InvoiceStatus::cancelled()->getIndex()]
                                  ])->get();
                            if (!$invoice->shadow) {
                                $commissionService = App::make(CommissionService::class);
                                $salesPersons = $order->project->salesPersons ?? collect();
                                $leadGens = $order->project->leadGens ?? collect();

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

                                $salesPeoples = collect($salesPeoples)->unique('id')->values()->all();
                                $commissionService->createCommissionPercentagesFromInvoice($invoice);
                                $commissionService->addLeadGenerationCustomer(
                                    $salesPeoples,
                                    $order->project->contact->customer_id,
                                    $order->project_id,
                                    $invoice->id,
                                    $invoice->pay_date
                                );
                            }
                            shareOrderGrossMargin($invoice->order);
                        }
                    }
                }
            } else {
                if (InvoiceStatus::isPaid($invoice->status)) {
                    $purchaseOrder = $invoice->purchaseOrder;
                    if ($purchaseOrder) {
                          $purchaseOrder->update([
                          'status' => PurchaseOrderStatus::paid()->getIndex(),
                          'pay_date' => $invoice->pay_date,
                          'processed_by' => auth()->user()->id,
                              ]);
                                $resourceId = $purchaseOrder->resource_id;
                        if ($resourceId) {
                            $resourceService = App::make(ResourceService::class);
                            $resource = $resourceService->findBorrowedResource($resourceId);
                            if (!$resource) {
                                  $employeeService = App::make(EmployeeService::class);
                                  $resource = $employeeService->findBorrowedEmployee($resourceId);
                            }
                            if ($resource->email) {
                                $currencyFormatter = new \NumberFormatter('en-US', \NumberFormatter::CURRENCY);
                                $currency = CurrencyCode::make($purchaseOrder->currency_code)->getValue();
                                $price = $currencyFormatter->formatCurrency($purchaseOrder->manual_price, $currency);
                                Mail::to($resource->email)
                                ->queue(new PurchaseOrderPaid(
                                    $companyId,
                                    $resource->name,
                                    $purchaseOrder->number,
                                    $price
                                ));
                            }
                        }
                    }
                }
            }
        }

        if (auth()->user() && !(auth()->user() instanceof Resource) && UserRole::isAccountant(auth()->user()->role) && InvoiceStatus::isAuthorised($invoice->status) && InvoiceType::isAccrec($invoice->type)) {
            if ($invoice->isDirty('date') || $invoice->isDirty('due_date') || $invoice->isDirty('currency_code') || $invoice->isDirty('total_price')) {
                $invoice->status = InvoiceStatus::approval()->getIndex();
                $invoice->save();
            }
        }

        if ($invoice->isDirty('currency_code')) {
            $currencyRates = getCurrencyRates();
            $customerCurrency = CurrencyCode::make($invoice->currency_code)->__toString();
            if (Company::find($companyId)->currency_code == CurrencyCode::USD()->getIndex()) {
                $invoice->currency_rate_customer = (1 /$currencyRates['rates']['USD']) * $currencyRates['rates'][$customerCurrency];
            } else {
                $invoice->currency_rate_customer = $currencyRates['rates'][$customerCurrency];
            }
            $invoice->saveQuietly();

            if ($invoice->manual_input) {
                $itemService = App::make(ItemService::class);
                $itemService->savePricesAndCurrencyRates($companyId, $invoice->id, Invoice::class);
            }
        }

        if ($invoice->isDirty('vat_status') || $invoice->isDirty('vat_percentage')) {
            $itemService = App::make(ItemService::class);
            $itemService->savePricesAndCurrencyRates($companyId, $invoice->id, Invoice::class);
        }



        $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
        $legalEntity = $legalEntityRepository->firstByIdOrNull($invoice->legal_entity_id);

        if (!$invoice->shadow && $legalEntity && (new XeroAuthService($legalEntity))->exists()) {
            try {
                if (!$invoice->xero_id && $invoice->created_at->diffInSeconds(now()) > 60) {
                    XeroUpdate::dispatch($companyId, 'created', Invoice::class, $invoice->id, $legalEntity->id)->onQueue('low');
                } else {
                    XeroUpdate::dispatch($companyId, 'updated', Invoice::class, $invoice->id, $legalEntity->id)->onQueue('low');
                }
            } catch (Exception $exception) {
            }
        }
    }
}
