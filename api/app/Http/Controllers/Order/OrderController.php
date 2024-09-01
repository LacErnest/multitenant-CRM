<?php

namespace App\Http\Controllers\Order;

use App\DTO\Orders\CreateOrderDTO;
use App\DTO\Orders\ShareOrderDTO;
use App\DTO\Orders\UpdateOrderDTO;
use App\DTO\Orders\UpdateOrderStatusDTO;
use App\Enums\TablePreferenceType;
use App\Enums\UserRole;
use App\Exports\DataTablesExport;
use App\Exports\OrderReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Export\ExportOrderRequest;
use App\Http\Requests\Item\ItemCreateRequest;
use App\Http\Requests\Item\ItemDeleteRequest;
use App\Http\Requests\Item\ItemUpdateRequest;
use App\Http\Requests\Order\OrderAddDocumentRequest;
use App\Http\Requests\Order\OrderCreateRequest;
use App\Http\Requests\Order\OrdersGetRequest;
use App\Http\Requests\Order\OrderStatusUpdateRequest;
use App\Http\Requests\Order\OrderUpdateRequest;
use App\Http\Requests\PriceModifier\PriceModifierCreateRequest;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Item\ModifierResource;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\Export\OrderExporter;
use App\Services\ItemService;
use App\Services\OrderService;
use App\Services\TablePreferenceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Export\ExportGetRequest;
use App\Http\Requests\Order\ShareOrderRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\UnauthorizedException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class OrderController
 */
class OrderController extends Controller
{
    protected string $model = Order::class;

    /**
     * @var OrderService $orderService
     */
    protected OrderService $orderService;

    /**
     * OrderController constructor.
     * @param ItemService $itemService
     * @param OrderService $orderService
     */
    public function __construct(
        Itemservice $itemService,
        OrderService $orderService
    ) {
        parent::__construct($itemService);
        $this->orderService = $orderService;
    }

    /**
     * Get all orders of a specific company
     * @param string $companyId
     * @param Request $request
     * @param null $entity
     * @return array
     */
    public function all(string $companyId, Request $request, $entity = null): array
    {
        if (!UserRole::isHr(auth()->user()->role)) {
            return parent::getAll($companyId, $request, TablePreferenceType::orders()->getIndex());
        }

        throw new UnauthorizedException();
    }

    /**
     * Get one order of a specific project
     * @param $company_id
     * @param $project_id
     * @param $order_id
     * @return JsonResponse
     */
    public function singleFromProject($company_id, $project_id, $order_id)
    {
        if (!UserRole::isHr(auth()->user()->role)) {
            if (UserRole::isPm_restricted(auth()->user()->role)) {
                $project = Project::findOrFail($project_id);
                if ($project->project_manager_id != auth()->user()->id) {
                    throw new UnauthorizedException();
                }
            }

            if (UserRole::isSales(auth()->user()->role)) {
                $project = Project::findOrFail($project_id);
                $salesIds = User::where('email', auth()->user()->email)->pluck('id')->toArray();
                $isSalesPerson = Project::whereHas('salesPersons', function ($query) use ($salesIds) {
                    $query->whereIn('user_id', $salesIds);
                })->exists();
                $isLeadGen = Project::whereHas('leadGens', function ($query) use ($salesIds) {
                    $query->whereIn('user_id', $salesIds);
                })->exists();
                if (!$isSalesPerson && !$isLeadGen) {
                    throw new UnauthorizedException();
                }
            }
            return parent::getSingleFromProject($company_id, $project_id, $order_id);
        }
        throw new UnauthorizedException();
    }

    /**
     * Get certain orders using autocomplete
     * @param $company_id
     * @param $value
     * @param Request $request
     * @return JsonResponse
     */
    public function suggest($companyId, Request $request, $value = null): JsonResponse
    {
        $allOrders = false;
        $statusInput = $request->input('status', 1);
        if ($statusInput == 'all') {
            $allOrders = true;
        }
        return $this->orderService->suggest($companyId, $value, $allOrders);
    }

    /**
     * Create an order for a project
     * @param string $companyId
     * @param string $projectId
     * @param OrderCreateRequest $orderCreateRequest
     * @return JsonResponse
     */
    public function createOrder(string $companyId, string $projectId, OrderCreateRequest $orderCreateRequest)
    {
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($projectId);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        $orderDTO = new CreateOrderDTO($orderCreateRequest->allSafe());
        $order = $this->orderService->create($projectId, $orderDTO);

        return OrderResource::make($order->load('project.projectManager'))
          ->toResponse($orderCreateRequest)
          ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update an order for a project
     *
     * @param  string $companyId
     * @param  string $projectId
     * @param  string $orderId
     * @param  OrderUpdateRequest $request
     *
     * @return mixed
     */
    public function updateOrder(
        string $companyId,
        string $projectId,
        string $orderId,
        OrderUpdateRequest $request
    ) {
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($projectId);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        $payload = new UpdateOrderDTO($request->allSafe());
        $order = $this->orderService->update($projectId, $orderId, $payload);

        return OrderResource::make($order);
    }

    /**
     * @param  string $companyId
     * @param  string $projectId
     * @param  string $orderId
     *
     * @param  OrderStatusUpdateRequest $request
     *
     * @return OrderResource
     */
    public function updateOrderStatus(
        string $companyId,
        string $projectId,
        string $orderId,
        OrderStatusUpdateRequest $request
    ) {
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($projectId);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        $payload = new UpdateOrderStatusDTO($request->allSafe());
        $order = $this->orderService->update($projectId, $orderId, $payload);

        return OrderResource::make($order);
    }

    /**
     * Update order sharing state
     * @param  string $companyId
     * @param  string $projectId
     * @param  string $orderId
     * @param  ShareOrderRequest $request
     * @return JsonResponse
     */
    public function shareOrder(
        string $companyId,
        string $projectId,
        string $orderId,
        ShareOrderRequest $request
    ): JsonResponse {
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($projectId);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }
        $payload = new ShareOrderDTO($request->allSafe());
        $order = $this->orderService->update($projectId, $orderId, $payload);
        $this->itemService->assignItemsToTheCompany($companyId, $order);
        return response()->json($order->master === $payload->getMaster());
    }

    /**
     * Check sharing permissions for an order
     * @param  string $companyId
     * @param  string $projectId
     * @param  string $orderId
     * @return JsonResponse
     */
    public function checkSharingPermissions(
        string $companyId,
        string $projectId,
        string $orderId
    ): JsonResponse {

        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($projectId);
            if ($project->project_manager_id != auth()->user()->id) {
                response()->json(false);
            }
        }

        $status = $this->orderService->checkSharingPermissions($orderId);

        return response()->json($status);
    }

    /**
     * Create a new order item
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $orderId
     * @param ItemCreateRequest $request
     * @return ItemResource
     */
    public function createItem(
        string $companyId,
        string $projectId,
        string $orderId,
        ItemCreateRequest $request
    ): ItemResource {
        $this->orderService->checkAuthorization($projectId);
        $this->orderService->checkOrderDelivered($orderId);
        if (checkCancelledStatus(Order::class, $orderId)) {
            throw new UnprocessableEntityHttpException(
                'Order is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::createItem($companyId, $projectId, $orderId, $request);
    }

    /**
     * Update order item
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $orderId
     * @param string $itemId
     * @param ItemUpdateRequest $request
     * @return ItemResource
     */
    public function updateItem(
        string $companyId,
        string $projectId,
        string $orderId,
        string $itemId,
        ItemUpdateRequest $request
    ): ItemResource {
        $this->orderService->checkAuthorization($projectId);
        $this->orderService->checkOrderDelivered($orderId);
        if (checkCancelledStatus(Order::class, $orderId)) {
            throw new UnprocessableEntityHttpException(
                'Order is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::updateItem($companyId, $projectId, $orderId, $itemId, $request);
    }

    /**
     * Delete order item
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $orderId
     * @param ItemDeleteRequest $request
     * @return Response
     */
    public function deleteItems(
        string $companyId,
        string $projectId,
        string $orderId,
        ItemDeleteRequest $request
    ): Response {
        $this->orderService->checkAuthorization($projectId);
        $this->orderService->checkOrderDelivered($orderId);
        if (checkCancelledStatus(Order::class, $orderId)) {
            throw new UnprocessableEntityHttpException(
                'Order is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::deleteItems($companyId, $projectId, $orderId, $request);
    }

    /**
     * Create a new price modifier for an order
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $orderId
     * @param PriceModifierCreateRequest $request
     * @return ModifierResource
     */
    public function createPriceModifier(
        string $companyId,
        string $projectId,
        string $orderId,
        PriceModifierCreateRequest $request
    ): ModifierResource {
        $this->orderService->checkAuthorization($projectId);
        $this->orderService->checkOrderDelivered($orderId);
        if (checkCancelledStatus(Order::class, $orderId)) {
            throw new UnprocessableEntityHttpException(
                'Order is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::createPriceModifier($companyId, $projectId, $orderId, $request);
    }

    /**
     * Update a price modifier for an order
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $orderId
     * @param string $priceModifierId
     * @param PriceModifierCreateRequest $request
     * @return ModifierResource
     */
    public function updatePriceModifier(
        string $companyId,
        string $projectId,
        string $orderId,
        string $priceModifierId,
        PriceModifierCreateRequest $request
    ): ModifierResource {
        $this->orderService->checkAuthorization($projectId);
        $this->orderService->checkOrderDelivered($orderId);
        if (checkCancelledStatus(Order::class, $orderId)) {
            throw new UnprocessableEntityHttpException(
                'Order is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::updatePriceModifier($companyId, $projectId, $orderId, $priceModifierId, $request);
    }

    /**
     * Delete a price modifier for an order
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $orderId
     * @param string $priceModifierId
     * @return mixed
     */
    public function deletePriceModifier(
        string $companyId,
        string $projectId,
        string $orderId,
        string $priceModifierId
    ): Response {
        $this->orderService->checkAuthorization($projectId);
        $this->orderService->checkOrderDelivered($orderId);
        if (checkCancelledStatus(Order::class, $orderId)) {
            throw new UnprocessableEntityHttpException(
                'Order is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::deletePriceModifier($companyId, $projectId, $orderId, $priceModifierId);
    }

    /**
     * Export data from order
     * @param ExportGetRequest $request
     * @param $company_id
     * @param $project_id
     * @param $order_id
     * @param $format
     */

    public function export(ExportOrderRequest $request, $company_id, $project_id, $order_id, $template_id, $format)
    {
        if (!$project = Project::find($project_id)) {
            throw new ModelNotFoundException();
        }

        if (!$order = $project->order()->find($order_id)) {
            throw new ModelNotFoundException();
        }

        if (UserRole::isPm_restricted(auth()->user()->role)) {
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        return (new OrderExporter())->export($order, $template_id, $format);
    }

    public function exportReport($company_id, $project_id, $order_id)
    {
        if (!$project = Project::find($project_id)) {
            throw new ModelNotFoundException();
        }

        if (!$order = $project->order()->find($order_id)) {
            throw new ModelNotFoundException();
        }

        $export = new OrderReportExport($order);

        return Excel::download($export, 'order_report_' . date('Y-m-d') . '.xlsx');
    }

    public function addDocument(
        OrderAddDocumentRequest $addDocumentRequest,
        string $companyId,
        string $projectId,
        string $orderId
    ) {
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($projectId);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        $file = $addDocumentRequest->input('file');
        $this->orderService->uploadDocument($orderId, $file);

        return response()->json()->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    public function deleteDocument(
        string $companyId,
        string $projectId,
        string $orderId
    ) {
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($projectId);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        $this->orderService->deleteDocument($orderId);

        return response()->json()->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    /**
     * Export all orders to excel
     *
     * @param string $companyId
     * @param OrdersGetRequest $request
     * @return BinaryFileResponse
     */
    public function exportDataTable(string $companyId, OrdersGetRequest $request): BinaryFileResponse
    {
        $amount = Order::count();
        $request->merge([
          'amount' => $amount,
          'page' => 0,
        ]);

        $tablePrefService = App::make(TablePreferenceService::class);
        $tablePrefs = $tablePrefService->getPreferences('users', TablePreferenceType::orders()->getIndex())->getData();
        $columns = $tablePrefs->columns;
        $orders = parent::getAll($companyId, $request, TablePreferenceType::orders()->getIndex());
        $export = new DataTablesExport($orders['data'], $columns);

        return Excel::download($export, 'orders_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Get all parsed orders
     *
     * Retrieves all parsed orders based on user role permissions.
     *
     * @OA\Get(
     *     path="/api/orders-parsed",
     *     summary="Get all parsed orders",
     *     tags={"Orders"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User does not have necessary permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return JsonResponse
     * @throws UnauthorizedException
     */
    public function getAllParsed(Request $request): JsonResponse
    {
        if (UserRole::isSuper_admin(auth()->user()->role) || UserRole::isAdmin(auth()->user()->role) || UserRole::isOwner(auth()->user()->role) ||
            UserRole::isSales(auth()->user()->role) || UserRole::isAccountant(auth()->user()->role) ||
            UserRole::isOwner_read(auth()->user()->role)) {
                return response()->json($this->orderService->getAllParsed());
        }
        throw new UnauthorizedException();
    }
}
