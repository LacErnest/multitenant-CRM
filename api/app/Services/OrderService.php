<?php

namespace App\Services;

use App\Contracts\DTO\Orders\OrderModificationDTO;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\DTO\Orders\CreateOrderDTO;
use App\DTO\Orders\UpdateOrderDTO;
use App\DTO\Orders\UpdateOrderStatusDTO;
use App\Enums\CustomerStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Jobs\MarkUpNotificationJob;
use App\Mail\MarkUpNotification;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Repositories\OrderRepository;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tenancy\Facades\Tenancy;

class OrderService
{
    /**
     * @var OrderRepository
     * @deprecated
     */
    protected OrderRepository $order_repository;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var ProjectRepositoryInterface
     */
    private ProjectRepositoryInterface $projectRepository;

    private LegalEntityRepositoryInterface $legalEntityRepository;

    private LegalEntitySettingRepositoryInterface $legalSettingRepository;

    private CommissionService $commissionService;

    /**
     * OrderService constructor.
     * @param OrderRepository $order_repository
     * @param OrderRepositoryInterface $orderRepository
     * @param ProjectRepositoryInterface $projectRepository
     * @param LegalEntityRepositoryInterface $legalEntityRepository
     * @param LegalEntitySettingRepositoryInterface $legalSettingRepository
     * @param CommissionService $commissionService
     */
    public function __construct(
        OrderRepository $order_repository,
        OrderRepositoryInterface $orderRepository,
        ProjectRepositoryInterface $projectRepository,
        LegalEntityRepositoryInterface $legalEntityRepository,
        LegalEntitySettingRepositoryInterface $legalSettingRepository,
        CommissionService $commissionService
    ) {
        $this->order_repository = $order_repository;
        $this->orderRepository = $orderRepository;
        $this->projectRepository = $projectRepository;
        $this->legalEntityRepository = $legalEntityRepository;
        $this->legalSettingRepository = $legalSettingRepository;
        $this->commissionService = $commissionService;
    }

    public function create(string $projectId, CreateOrderDTO $createOrderDTO): Order
    {
        if (!$project = $this->projectRepository->firstByIdOrNull($projectId)) {
            throw new UnprocessableEntityHttpException('Destination project does not exist');
        } elseif (!($project->order === null)) {
            throw new UnprocessableEntityHttpException('Order already exists for this project');
        }

        $legalEntity = $this->legalEntityRepository->firstById($createOrderDTO->legal_entity_id);
        $format = $this->legalSettingRepository->firstById($legalEntity->legal_entity_setting_id);

        $order = $this->orderRepository->create(
            array_merge($createOrderDTO->toArray(), [
                'project_id' => $projectId,
                'number' => transformFormat($format->order_number_format, $format->order_number + 1),
                'status' => OrderStatus::draft()->getIndex(),
            ])
        );

        $this->legalSettingRepository->update($format->id, ['order_number' => $format->order_number + 1]);

        if ($createOrderDTO->project_manager_id) {
            $this->projectRepository->update($projectId, ['project_manager_id' => $createOrderDTO->project_manager_id]);
        }

        if ($order->project->contact->customer->status != CustomerStatus::active()->getIndex()) {
            $customerRepository = App::make(CustomerRepositoryInterface::class);
            $customerRepository->update(
                $order->project->contact->customer->id,
                ['status' => CustomerStatus::active()->getIndex()]
            );
        }

        return $order;
    }

    /**
     * @param string $projectId
     * @param string $orderId
     * @param OrderModificationDTO $orderModificationDTO
     * @return Order
     * @throws Exception
     */
    public function update(
        string $projectId,
        string $orderId,
        OrderModificationDTO $orderModificationDTO
    ): Order {
        DB::beginTransaction();
        try {
            $order = $this->orderRepository->firstById($orderId);
            $oldDate = $order->date;

            if (!$this->isOrderRelatedToProject($orderId, $projectId)) {
                throw new UnprocessableEntityHttpException('Wrong project for the order given.');
            }

            $payload = $orderModificationDTO->toArray();

          // status updates
            if ($orderModificationDTO instanceof UpdateOrderStatusDTO) {
                if (OrderStatus::isDelivered($orderModificationDTO->getStatus())) {
                    if (false === OrderStatus::isActive($order->status)) {
                        throw new UnprocessableEntityHttpException('Order is not active.');
                    }

                    $payload = $this->order_repository->changeOrderStatus($order, $projectId, $payload);
                }
            }

          // order updates
            if ($orderModificationDTO instanceof UpdateOrderDTO && $orderModificationDTO->getProjectManagerId()) {
                if (!$this->isOrderEditable($orderId)) {
                    throw new UnprocessableEntityHttpException('Order has been delivered. No updates allowed.');
                }

                $this->projectRepository->update($projectId, [
                'project_manager_id' => $orderModificationDTO->getProjectManagerId()
                ]);
            }

            if (!$order->shadow) {
                $this->orderRepository->update($orderId, $payload);
            }

            if ($order->master) {
                $shadowOrders = $order->shadows;
                $company = Company::find(getTenantWithConnection());
                foreach ($shadowOrders as $shadowOrder) {
                    Tenancy::setTenant(Company::find($shadowOrder->shadow_company_id));
                    $this->orderRepository->update($shadowOrder->shadow_id, $payload);
                }
                Tenancy::setTenant($company);
            }

            DB::commit();

            if (Arr::exists($payload, 'date') && !($payload['date'] == $oldDate) && !$order->shadow) {
                $itemService = App::make(ItemService::class);
                $itemService->savePricesAndCurrencyRates(getTenantWithConnection(), $order->id, Order::class);
            }

            return $order->refresh();
        } catch (Exception $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    public function suggest(string $companyId, string $value, bool $allOrders)
    {
        return $this->order_repository->suggest($companyId, $value, $allOrders);
    }

    public function checkAuthorization(string $projectId): void
    {
        if (UserRole::isSales(auth()->user()->role) || UserRole::isHr(auth()->user()->role) ||
          UserRole::isOwner_read(auth()->user()->role)) {
            throw new UnauthorizedException();
        }

        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($projectId);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }
    }

    public function checkOrderDelivered(string $orderId): void
    {
        $order = $this->orderRepository->firstById($orderId);

        if (OrderStatus::isDelivered($order->status) || OrderStatus::isInvoiced($order->status)) {
            throw new UnprocessableEntityHttpException(
                'Order has been delivered. No create, update, delete of items, price modifiers allowed.'
            );
        }

        if ($order->shadow) {
            throw new UnprocessableEntityHttpException(
                'Shadow order. No updates allowed.'
            );
        }
    }

    /**
     * @param  string $orderId
     *
     * @return bool
     */
    public function isOrderEditable(string $orderId): bool
    {
        $order = $this->orderRepository->firstById($orderId);

        return !OrderStatus::isDelivered($order->status) || !OrderStatus::isInvoiced($order->status);
    }

    /**
     * @param  string $orderId
     * @param  string $projectId
     *
     * @return bool
     */
    public function isOrderRelatedToProject(string $orderId, string $projectId): bool
    {
        /** @var Order $order */
        $order = $this->orderRepository->firstById($orderId);

        return $order->project_id === $projectId;
    }

    public function uploadDocument(string $orderId, $file)
    {
        $order = $this->orderRepository->firstById($orderId);
        $mimetype = getDocumentMimeType($file);
        $order->addMediaFromBase64($file)->usingFileName($order->number . '.' . $mimetype)->toMediaCollection('document_order');
    }

    public function deleteDocument(string $orderId)
    {
        $order = $this->orderRepository->firstById($orderId);

        if ($media = $order->getMedia('document_order')->first()) {
            $media->delete();
        }
    }

    public function markUpCheck(Project $project, string $companyId)
    {
        if (!$project->purchase_order_project) {
            $purchaseOrders = $project->purchaseOrders()->whereIn('status', [PurchaseOrderStatus::submitted()->getIndex(),
            PurchaseOrderStatus::completed()->getIndex(), PurchaseOrderStatus::authorised()->getIndex(),
            PurchaseOrderStatus::billed()->getIndex(), PurchaseOrderStatus::paid()->getIndex()])->get();

            $costs = $purchaseOrders->sum('total_price');
            $vat = $purchaseOrders->sum('total_vat');
            $totalCosts = ($costs - $vat) + $project->employee_costs;
            $order = $project->order;
            $budget = $order->total_price - $order->total_vat;

            $markup = $budget > 0 ? flooring(safeDivide($budget - $totalCosts, $budget) * 100, 2) : null;
            if ($markup && $markup < 50) {
                if (!App::runningInConsole()) {
                    MarkUpNotificationJob::dispatch($companyId, $order->id, (string)$markup)->onQueue('low');
                }
            }
        }
    }

    /**
     * @param string $orderId
     * @return bool
     */
    public function checkSharingPermissions(string $orderId): bool
    {
        $order = $this->orderRepository->firstById($orderId);
        return $order->shadows->count() == 0 ?? false;
    }

    public function getAllParsed()
    {
        $user = auth()->user();
        $company_id = $user->company_id;
        $parsed_result = [];

        if (UserRole::isSuperAdmin($user->role)  || UserRole::isAdmin($user->role)) {
            $companies = Company::all();
        } else {
            if (!$company_id) {
                return $parsed_result;
            }
            $companies = [Company::findOrFail($company_id)];
        }

        foreach ($companies as $company) {
            Tenancy::setTenant($company);

            $ordersQuery = Order::whereIn('status', [
                OrderStatus::delivered()->getIndex(),
                OrderStatus::invoiced()->getIndex()
            ]);

            if (UserRole::isPm_restricted($user->role)) {
                $ordersQuery->whereHas('project', function ($query) use ($user) {
                    $query->where('project_manager_id', $user->id);
                });
            }

            $orders = $ordersQuery->get();

            foreach ($orders as $order) {
                if ($order->shadow) {
                    continue;
                }

                $invoices = Invoice::where([
                    ['order_id', $order->id],
                    ['status', InvoiceStatus::paid()->getIndex()],
                    ['type', InvoiceType::accrec()->getIndex()],
                    ['shadow', false]
                ])->get();

                if (count($invoices) < 1) {
                    continue;
                }

                $parsedInvoices = [];
                foreach ($invoices as $invoice) {
                    $parsedInvoices[] = [
                        'id' => $invoice->id,
                        'number' => $invoice->number,
                        'order' => $invoice->order->number
                    ];
                }

                $parsed_result[(string) $company->id][] = [
                    'order_id' => $order->id,
                    'order' => $order->number,
                    'quote_id' => $order->quote_id,
                    'order_status' => $order->status,
                    'invoices' => $parsedInvoices
                ];
            }
        }

        return $parsed_result;
    }
}
