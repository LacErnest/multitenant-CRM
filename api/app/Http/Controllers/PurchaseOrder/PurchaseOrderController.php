<?php

namespace App\Http\Controllers\PurchaseOrder;

use App\DTO\PurchaseOrders\CreatePurchaseOrderDTO;
use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\TablePreferenceType;
use App\Enums\UserRole;
use App\Exports\DataTablesExport;
use App\Http\Requests\Export\ExportGetRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Export\ExportPurchaseOrderRequest;
use App\Http\Requests\Item\ItemCreateRequest;
use App\Http\Requests\Item\ItemDeleteRequest;
use App\Http\Requests\Item\ItemUpdateRequest;
use App\Http\Requests\PriceModifier\PriceModifierCreateRequest;
use App\Http\Requests\PurchaseOrder\CreateIndependentPORequest;
use App\Http\Requests\PurchaseOrder\PurchaseOrderCloneRequest;
use App\Http\Requests\PurchaseOrder\PurchaseOrderCreateRequest;
use App\Http\Requests\PurchaseOrder\PurchaseOrdersGetRequest;
use App\Http\Requests\PurchaseOrder\PurchaseOrderStatusUpdateRequest;
use App\Http\Requests\PurchaseOrder\PurchaseOrderUpdateRequest;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Item\ModifierResource;
use App\Http\Resources\PurchaseOrder\PurchaseOrderResource;
use App\Models\Item;
use App\Models\Order;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\TablePreference;
use App\Services\Export\PurchaseOrderExporter;
use App\Services\ItemService;
use App\Services\PurchaseOrderService;
use App\Services\TablePreferenceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\UnauthorizedException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PurchaseOrderController extends Controller
{
    protected string $model = PurchaseOrder::class;

    protected PurchaseOrderService $purchaseOrderService;

    public function __construct(Itemservice $itemService, PurchaseOrderService $purchaseOrderService)
    {
        parent::__construct($itemService);
        $this->purchaseOrderService = $purchaseOrderService;
    }

    /**
     * Get all purchase orders of a specific company
     * @param string $companyId
     * @param Request $request
     * @param null $entity
     * @return array
     */
    public function getAll(string $companyId, Request $request, $entity = null): array
    {
        if (!UserRole::isSales(auth()->user()->role) && !UserRole::isHr(auth()->user()->role)) {
            return parent::getAll($companyId, $request, TablePreferenceType::purchase_orders()->getIndex());
        }
        throw new UnauthorizedException();
    }

    /**
     * Get one purchase order of a specific project
     * @param $company_id
     * @param $project_id
     * @param $purchase_order_id
     * @return JsonResponse
     */
    public function getSingleFromProject($company_id, $project_id, $purchase_order_id)
    {
        if (!UserRole::isSales(auth()->user()->role) && !UserRole::isHr(auth()->user()->role)) {
            if (UserRole::isPm_restricted(auth()->user()->role)) {
                $project = Project::findOrFail($project_id);
                if ($project->project_manager_id != auth()->user()->id) {
                    throw new UnauthorizedException();
                }
            }
            return parent::getSingleFromProject($company_id, $project_id, $purchase_order_id);
        }
        throw new UnauthorizedException();
    }

    /**
     * Create an purchase order for a project
     * @param string $companyId
     * @param string $projectId
     * @param PurchaseOrderCreateRequest $purchaseOrderCreateRequest
     * @return JsonResponse
     */
    public function createPurchaseOrder(
        string $companyId,
        string $projectId,
        PurchaseOrderCreateRequest $purchaseOrderCreateRequest
    ): JsonResponse {
        $purchaseOrderDTO = new CreatePurchaseOrderDTO($purchaseOrderCreateRequest->allSafe());
        $purchaseOrder = $this->purchaseOrderService->create($projectId, $purchaseOrderDTO);

        return PurchaseOrderResource::make($purchaseOrder)
          ->toResponse($purchaseOrderCreateRequest)
          ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a purchase order for a project
     * @param $company_id
     * @param $project_id
     * @param $purchase_order_id
     * @param PurchaseOrderUpdateRequest $request
     * @return PurchaseOrderResource
     */
    public function updatePurchaseOrder($company_id, $project_id, $purchase_order_id, PurchaseOrderUpdateRequest $request)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($purchase_order_id);
        if ($purchaseOrder->project_id === $project_id) {
            $purchaseOrder = $this->purchaseOrderService->update($purchaseOrder, $request);
            return PurchaseOrderResource::make($purchaseOrder);
        }
        throw new ModelNotFoundException();
    }

    /**
     * @param $company_id
     * @param $project_id
     * @param $purchase_order_id
     * @param PurchaseOrderStatusUpdateRequest $request
     * @return PurchaseOrderResource
     */
    public function updatePurchaseOrderStatus($company_id, $project_id, $purchase_order_id, PurchaseOrderStatusUpdateRequest $request)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($purchase_order_id);
        if ($purchaseOrder->project_id === $project_id) {
            if ($purchaseOrder->payment_terms < 30 && (PurchaseOrderStatus::isSubmitted($request->input('status')) ||
                PurchaseOrderStatus::isAuthorised($request->input('status'))) &&
                !in_array(auth()->user()->role, [UserRole::admin()->getIndex(), UserRole::owner()->getIndex()])) {
                throw new UnprocessableEntityHttpException('Payment terms less than 30 days. Approval needed by admin or owner.');
            }

            $purchaseOrder = $this->purchaseOrderService->update($purchaseOrder, $request);

            return PurchaseOrderResource::make($purchaseOrder);
        }
        throw new ModelNotFoundException();
    }

    /**
     * Clone a purchase order
     * @param $company_id
     * @param $project_id
     * @param $purchase_order_id
     * @param PurchaseOrderCloneRequest $request
     * @return JsonResponse
     */
    public function clone($company_id, $project_id, $purchase_order_id, PurchaseOrderCloneRequest $request)
    {

        if (!$purchaseOrder = PurchaseOrder::with('project')->find($purchase_order_id)) {
            return response()->json(['message' => 'cannot be cloned'], 400);
        }
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            if ($purchaseOrder->project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        if ($purchaseOrder->project->purchase_order_project) {
            $destination_id = 'current';
        } else {
            $destination_id = $request->input('destination_id');
        }

        if ($destination_id == 'current') {
            $destination_id = $project_id;
        } else {
            $destinationOrder = Order::find($destination_id);

            if (!$destinationOrder) {
                return response()->json(['message' => 'Could not find requested order.'], 404);
            } else {
                $hasDraftOrder = OrderStatus::isDraft($destinationOrder->status);

                if ($hasDraftOrder) {
                    return response()->json(['message' => 'Selected order has status draft.'], 422);
                }
                if (UserRole::isPm_restricted(auth()->user()->role)) {
                    if ($destinationOrder->project->project_manager_id != auth()->user()->id) {
                        throw new UnauthorizedException();
                    }
                }

                $destination_id = $destinationOrder->project_id;
            }
        }

        $purchaseNumber = getPurchaseOrderDraftNumber();

        return parent::cloneEntity($company_id, $project_id, $purchase_order_id, $purchaseNumber, $destination_id, PurchaseOrder::class);
    }

    /**
     * Create a new purchase order item
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $purchaseOrderId
     * @param ItemCreateRequest $request
     * @return ItemResource
     */
    public function createItem(
        string $companyId,
        string $projectId,
        string $purchaseOrderId,
        ItemCreateRequest $request
    ): ItemResource {

        $this->purchaseOrderService->checkAuthorization();
        $this->purchaseOrderService->checkIsDraft($purchaseOrderId);
        $this->purchaseOrderService->checkIsPmRestricted($projectId);
        if (checkCancelledStatus(PurchaseOrder::class, $purchaseOrderId)) {
            throw new UnprocessableEntityHttpException(
                'Purchase Order is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::createItem($companyId, $projectId, $purchaseOrderId, $request);
    }

    /**
     * Update purchase order item
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $purchaseOrderId
     * @param string $itemId
     * @param ItemUpdateRequest $request
     * @return ItemResource
     */
    public function updateItem(
        string $companyId,
        string $projectId,
        string $purchaseOrderId,
        string $itemId,
        ItemUpdateRequest $request
    ): ItemResource {
        $this->purchaseOrderService->checkAuthorization();
        $this->purchaseOrderService->checkIsDraft($purchaseOrderId);
        $this->purchaseOrderService->checkIsPmRestricted($projectId);
        if (checkCancelledStatus(PurchaseOrder::class, $purchaseOrderId)) {
            throw new UnprocessableEntityHttpException(
                'Purchase Order is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::updateItem($companyId, $projectId, $purchaseOrderId, $itemId, $request);
    }

    /**
     * Delete purchase order item
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $purchaseOrderId
     * @param ItemDeleteRequest $request
     * @return Response
     */
    public function deleteItems(
        string $companyId,
        string $projectId,
        string $purchaseOrderId,
        ItemDeleteRequest $request
    ): Response {
        $this->purchaseOrderService->checkAuthorization();
        $this->purchaseOrderService->checkIsDraft($purchaseOrderId);
        $this->purchaseOrderService->checkIsPmRestricted($projectId);
        if (checkCancelledStatus(PurchaseOrder::class, $purchaseOrderId)) {
            throw new UnprocessableEntityHttpException(
                'Purchase Order is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::deleteItems($companyId, $projectId, $purchaseOrderId, $request);
    }

    /**
     * Create a new price modifier for a purchase order
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $purchaseOrderId
     * @param PriceModifierCreateRequest $request
     * @return ModifierResource
     */
    public function createPriceModifier(
        string $companyId,
        string $projectId,
        string $purchaseOrderId,
        PriceModifierCreateRequest $request
    ): ModifierResource {
        $this->purchaseOrderService->checkAuthorization();
        $this->purchaseOrderService->checkIsDraft($purchaseOrderId);
        $this->purchaseOrderService->checkIsPmRestricted($projectId);
        if (checkCancelledStatus(PurchaseOrder::class, $purchaseOrderId)) {
            throw new UnprocessableEntityHttpException(
                'Purchase Order is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::createPriceModifier($companyId, $projectId, $purchaseOrderId, $request);
    }

    /**
     * Update a price modifier for a purchase order
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $purchaseOrderId
     * @param string $priceModifierId
     * @param PriceModifierCreateRequest $request
     * @return ModifierResource
     */
    public function updatePriceModifier(
        string $companyId,
        string $projectId,
        string $purchaseOrderId,
        string $priceModifierId,
        PriceModifierCreateRequest $request
    ): ModifierResource {
        $this->purchaseOrderService->checkAuthorization();
        $this->purchaseOrderService->checkIsDraft($purchaseOrderId);
        $this->purchaseOrderService->checkIsPmRestricted($projectId);
        if (checkCancelledStatus(PurchaseOrder::class, $purchaseOrderId)) {
            throw new UnprocessableEntityHttpException(
                'Purchase Order is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::updatePriceModifier($companyId, $projectId, $purchaseOrderId, $priceModifierId, $request);
    }

    /**
     * Delete a price modifier for a purchase order
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $purchaseOrderId
     * @param string $priceModifierId
     * @return mixed
     */
    public function deletePriceModifier(
        string $companyId,
        string $projectId,
        string $purchaseOrderId,
        string $priceModifierId
    ): Response {
        $this->purchaseOrderService->checkAuthorization();
        $this->purchaseOrderService->checkIsDraft($purchaseOrderId);
        $this->purchaseOrderService->checkIsPmRestricted($projectId);
        if (checkCancelledStatus(PurchaseOrder::class, $purchaseOrderId)) {
            throw new UnprocessableEntityHttpException(
                'Purchase Order is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::deletePriceModifier($companyId, $projectId, $purchaseOrderId, $priceModifierId);
    }

    /**
     * Export data from purchase order
     * @param ExportGetRequest $request
     * @param $company_id
     * @param $project_id
     * @param $order_id
     * @param $format
     * @return Response|BinaryFileResponse
     */

    public function export(ExportPurchaseOrderRequest $request, $company_id, $project_id, $order_id, $template_id, $format)
    {
        if (!$project = Project::find($project_id)) {
            throw new ModelNotFoundException();
        }

        if (!$order = $project->purchaseOrders()->find($order_id)) {
            throw new ModelNotFoundException();
        }

        if (UserRole::isPm_restricted(auth()->user()->role)) {
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        return (new PurchaseOrderExporter())->export($order, $template_id, $format);
    }

    /**
     * Export all purchase orders to excel
     *
     * @param string $companyId
     * @param PurchaseOrdersGetRequest $request
     * @return BinaryFileResponse
     */
    public function exportDataTable(string $companyId, PurchaseOrdersGetRequest $request): BinaryFileResponse
    {
        $amount = PurchaseOrder::count();

        $request->merge([
          'amount' => $amount,
          'page' => 0,
        ]);

        $tablePreferenceType = TablePreferenceType::purchase_orders();
        if (!empty($request->project)) {
            $tablePreferenceType = TablePreferenceType::project_purchase_orders();
        }
        $tablePrefService = App::make(TablePreferenceService::class);
        $tablePrefs = $tablePrefService->getPreferences('users', $tablePreferenceType->getIndex())->getData();
        $columns = $tablePrefs->columns;
        $purchaseOrders = parent::getAll($companyId, $request, $tablePreferenceType->getIndex());
        $export = new DataTablesExport($purchaseOrders['data'], $columns);

        return Excel::download($export, 'purchaseOrders_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Get all purchase orders belonging to a project
     * @param string $companyId
     * @param string $projectId
     * @param Request $request
     * @param null $entity
     * @return array
     */
    public function getAllFromProject(string $companyId, string $projectId, Request $request, $entity = null): array
    {
        $request['project'] = $projectId;

        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($projectId);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        return parent::getAll($companyId, $request, TablePreferenceType::project_purchase_orders()->getIndex());
    }

    /**
     * Create a purchase order
     * @param string $companyId
     * @param CreateIndependentPORequest $createIndependentPORequest
     * @return JsonResponse
     */
    public function createIndependentPurchaseOrder(
        string $companyId,
        CreateIndependentPORequest $createIndependentPORequest
    ): JsonResponse {
        $purchaseOrderDTO = new CreatePurchaseOrderDTO($createIndependentPORequest->allSafe());
        $purchaseOrder = $this->purchaseOrderService->createIndependent($purchaseOrderDTO);

        return PurchaseOrderResource::make($purchaseOrder)
          ->toResponse($createIndependentPORequest)
          ->setStatusCode(Response::HTTP_CREATED);
    }
}
