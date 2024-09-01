<?php

namespace App\Http\Controllers\Invoice;

use App\DTO\Invoices\CreateInvoiceDTO;
use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Enums\TablePreferenceType;
use App\Enums\UserRole;
use App\Exports\DataTablesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Export\ExportGetRequest;
use App\Http\Requests\Invoice\InvoiceCloneRequest;
use App\Http\Requests\Invoice\InvoiceCreateRequest;
use App\Http\Requests\Invoice\InvoicesGetRequest;
use App\Http\Requests\Invoice\InvoiceStatusUpdateRequest;
use App\Http\Requests\Invoice\InvoiceUpdateRequest;
use App\Http\Requests\Item\ItemCreateRequest;
use App\Http\Requests\Item\ItemDeleteRequest;
use App\Http\Requests\Item\ItemUpdateRequest;
use App\Http\Requests\PriceModifier\PriceModifierCreateRequest;
use App\Http\Resources\EmailTemplate\EmailTemplateResource;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Item\ModifierResource;
use App\Models\Invoice;
use App\Models\Company;
use App\Models\Order;
use App\Models\Project;
use App\Models\TablePreference;
use App\Services\Export\InvoiceExporter;
use App\Services\InvoiceService;
use App\Services\ItemService;
use App\Services\TablePreferenceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use Maatwebsite\Excel\Facades\Excel;
use Swift_IoException;
use Swift_TransportException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class InvoiceController extends Controller
{
    protected string $model = Invoice::class;

    protected InvoiceService $invoiceService;

    public function __construct(Itemservice $itemService, InvoiceService $invoiceService)
    {
        parent::__construct($itemService);
        $this->invoiceService = $invoiceService;
    }

  /**
   * Get all invoices of a specific company
   * @param $company_id
   * @param Request $request
   * @param null $entity
   * @return array
   */
    public function getAll(string $companyId, Request $request, $entity = null): array
    {
        if (!UserRole::isHr(auth()->user()->role)) {
            return parent::getAll($companyId, $request, TablePreferenceType::invoices()->getIndex());
        }
        throw new UnauthorizedException();
    }

    public function getResourceInvoices($companyId, Request $request, $entity = null): array
    {
        return parent::getAll($companyId, $request, TablePreferenceType::resource_invoices()->getIndex());
    }

  /**
   * Get one invoice of a specific project
   * @param $company_id
   * @param $project_id
   * @param $invoice_id
   * @return JsonResponse
   */
    public function getSingleFromProject($company_id, $project_id, $invoice_id)
    {

        if (UserRole::isSales(auth()->user()->role) || UserRole::isHr(auth()->user()->role)) {
            throw new UnauthorizedException();
        }
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($project_id);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        return parent::getSingleFromProject($company_id, $project_id, $invoice_id);
    }

  /**
   * Create an invoice for a project
   * @param string $companyId
   * @param string $projectId
   * @param InvoiceCreateRequest $invoiceCreateRequest
   * @return JsonResponse
   */
    public function createInvoice(
        string $companyId,
        string $projectId,
        InvoiceCreateRequest $invoiceCreateRequest
    ): JsonResponse {
        $invoiceDTO = new CreateInvoiceDTO($invoiceCreateRequest->allSafe());
        $invoice = $this->invoiceService->create($projectId, $invoiceDTO);

        return InvoiceResource::make($invoice)->toResponse($invoiceCreateRequest)->setStatusCode(Response::HTTP_CREATED);
    }

  /**
   * Update an invoice for a project
   * @param $company_id
   * @param $project_id
   * @param $invoice_id
   * @param InvoiceUpdateRequest $request
   * @return mixed
   */
    public function updateInvoice($company_id, $project_id, $invoice_id, InvoiceUpdateRequest $request)
    {
        $invoice = Invoice::findOrFail($invoice_id);
        if ($invoice->project_id === $project_id) {
            if (InvoiceType::isAccpay($invoice->type) && ($invoice->purchase_order_id === null)) {
                return response()->json(['message' => "Payable invoice, can't update."], 422);
            }
            if (InvoiceStatus::isPaid($invoice->status) || InvoiceStatus::isSubmitted($invoice->status) || InvoiceStatus::isUnpaid($invoice->status)) {
                return response()->json(['message' => 'Invoice has been submitted. No updates allowed'], 422);
            }
            $invoice = $this->invoiceService->update($invoice, $request);
            return InvoiceResource::make($invoice);
        }
        throw new ModelNotFoundException();
    }

  /**
   * Update an invoice status for a project
   * @param $company_id
   * @param $project_id
   * @param $invoice_id
   * @param InvoiceStatusUpdateRequest $request
   * @return InvoiceResource
   */
    public function updateInvoiceStatus($company_id, $project_id, $invoice_id, InvoiceStatusUpdateRequest $request)
    {
        $invoice = Invoice::with('project')->findOrFail($invoice_id);
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            if ($invoice->project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }
        if (!$invoice->legal_entity_id) {
            if (InvoiceType::isAccrec($invoice->type) && !InvoiceStatus::isCancelled($request->input('status'))) {
                throw new UnprocessableEntityHttpException('Legal entity is not set yet.');
            }

            if (
            InvoiceType::isAccpay($invoice->type) &&
            (!InvoiceStatus::isCancelled($request->input('status')) &&
            !InvoiceStatus::isApproval($request->input('status'))) && !InvoiceStatus::isRejected($request->input('status'))
            ) {
                throw new UnprocessableEntityHttpException('Legal entity is not set yet.');
            }
        }

        if ($invoice->project_id === $project_id) {
            $message = null;
            try {
                $updatedInvoice = $this->invoiceService->update($invoice, $request);
                return response()->json([
                'invoice' => InvoiceResource::make($updatedInvoice),
                'status' => 'success',
                'message' => $message
                ]);
            } catch (\Exception $exception) {
                Log::error($exception->getMessage());
                $updatedInvoice = Invoice::findOrFail($invoice->id);
                if ($exception instanceof Swift_IoException) {
                    $message = 'Email has not been submitted to the customer';
                } else {
                    $message = $exception->getMessage();
                }
            }
            return response()->json([
            'invoice' => InvoiceResource::make($updatedInvoice),
            'status' => 'error',
            'message' => $message
            ]);
        }
        throw new ModelNotFoundException();
    }

  /**
   * Clone an invoice
   * @param $company_id
   * @param $project_id
   * @param $invoice_id
   * @param InvoiceCloneRequest $request
   * @return JsonResponse
   */
    public function clone($company_id, $project_id, $invoice_id, InvoiceCloneRequest $request)
    {
        $orderId = null;
        if (!$invoice = Invoice::find($invoice_id)) {
            return response()->json(['message' => 'cannot be cloned'], 400);
        }

        if (InvoiceType::isAccpay($invoice->type)) {
            return response()->json(['message' => "Payable invoice, can't clone."], 422);
        }

        $destination_id = $request->input('destination_id');

        if ($destination_id == 'current') {
            $destination_id = $project_id;
        } else {
            $destinationOrder = Order::find($destination_id);
            if (!$destinationOrder) {
                return response()->json(['message' => 'Could not find requested order.'], 404);
            } else {
                $hasInvoicedOrder = OrderStatus::isInvoiced($destinationOrder->status);
                $hasDraftOrder = OrderStatus::isDraft($destinationOrder->status);

                if ($hasInvoicedOrder) {
                    return response()->json(['message' => 'Selected order has already been invoiced.'], 422);
                } elseif ($hasDraftOrder) {
                    return response()->json(['message' => 'Selected order has status draft.'], 422);
                }

                $destination_id = $destinationOrder->project_id;
                $orderId = $destinationOrder->id;
            }
        }

        $draftInvoices = Invoice::where('number', 'like', 'Draft%')->orderByDesc('number')->get();

        if ($draftInvoices->isNotEmpty()) {
            $lastDraft = $draftInvoices->first();
            $lastNumber = str_replace('X', '', explode('-', $lastDraft->number)[1]);
        } else {
            $lastNumber = 0;
        }
        $invoiceNumber = transformFormat('DRAFT-XXXX', $lastNumber + 1);

        return parent::cloneEntity($company_id, $project_id, $invoice_id, $invoiceNumber, $destination_id, Invoice::class, $orderId);
    }

  /**
   * Create a new order item
   *
   * @param string $companyId
   * @param string $projectId
   * @param string $invoiceId
   * @param ItemCreateRequest $request
   * @return ItemResource
   */
    public function createItem(
        string $companyId,
        string $projectId,
        string $invoiceId,
        ItemCreateRequest $request
    ): ItemResource {
        $this->invoiceService->checkAuthorization();
        $this->invoiceService->checkInvoiceTypeAndStatus($invoiceId);
        if (checkCancelledStatus(Invoice::class, $invoiceId)) {
            throw new UnprocessableEntityHttpException(
                'Invoice is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::createItem($companyId, $projectId, $invoiceId, $request);
    }

  /**
   * Update order item
   *
   * @param string $companyId
   * @param string $projectId
   * @param string $invoiceId
   * @param string $itemId
   * @param ItemUpdateRequest $request
   * @return ItemResource
   */
    public function updateItem(
        string $companyId,
        string $projectId,
        string $invoiceId,
        string $itemId,
        ItemUpdateRequest $request
    ): ItemResource {
        $this->invoiceService->checkAuthorization();
        $this->invoiceService->checkInvoiceTypeAndStatus($invoiceId);
        if (checkCancelledStatus(Invoice::class, $invoiceId)) {
            throw new UnprocessableEntityHttpException(
                'Invoice is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::updateItem($companyId, $projectId, $invoiceId, $itemId, $request);
    }

  /**
   * Delete order items
   *
   * @param string $companyId
   * @param string $projectId
   * @param string $invoiceId
   * @param ItemDeleteRequest $request
   * @return Response
   */
    public function deleteItems(
        string $companyId,
        string $projectId,
        string $invoiceId,
        ItemDeleteRequest $request
    ): Response {
        $this->invoiceService->checkAuthorization();
        $this->invoiceService->checkInvoiceTypeAndStatus($invoiceId);
        if (checkCancelledStatus(Invoice::class, $invoiceId)) {
            throw new UnprocessableEntityHttpException(
                'Invoice is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::deleteItems($companyId, $projectId, $invoiceId, $request);
    }

  /**
   * Create a new price modifier for an invoice
   *
   * @param string $companyId
   * @param string $projectId
   * @param string $invoiceId
   * @param PriceModifierCreateRequest $request
   * @return ModifierResource
   */
    public function createPriceModifier(
        string $companyId,
        string $projectId,
        string $invoiceId,
        PriceModifierCreateRequest $request
    ): ModifierResource {
        $this->invoiceService->checkAuthorization();
        $this->invoiceService->checkInvoiceTypeAndStatus($invoiceId);
        if (checkCancelledStatus(Invoice::class, $invoiceId)) {
            throw new UnprocessableEntityHttpException(
                'Invoice is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::createPriceModifier($companyId, $projectId, $invoiceId, $request);
    }

  /**
   * Update a price modifier for an invoice
   *
   * @param string $companyId
   * @param string $projectId
   * @param string $invoiceId
   * @param string $priceModifierId
   * @param PriceModifierCreateRequest $request
   * @return ModifierResource
   */
    public function updatePriceModifier(
        string $companyId,
        string $projectId,
        string $invoiceId,
        string $priceModifierId,
        PriceModifierCreateRequest $request
    ): ModifierResource {
        $this->invoiceService->checkAuthorization();
        $this->invoiceService->checkInvoiceTypeAndStatus($invoiceId);
        if (checkCancelledStatus(Invoice::class, $invoiceId)) {
            throw new UnprocessableEntityHttpException(
                'Invoice is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::updatePriceModifier($companyId, $projectId, $invoiceId, $priceModifierId, $request);
    }

  /**
   * Delete a price modifier for an invoice
   *
   * @param string $companyId
   * @param string $projectId
   * @param string $invoiceId
   * @param string $priceModifierId
   * @return Response
   */
    public function deletePriceModifier(
        string $companyId,
        string $projectId,
        string $invoiceId,
        string $priceModifierId
    ): Response {
        $this->invoiceService->checkAuthorization();
        $this->invoiceService->checkInvoiceTypeAndStatus($invoiceId);
        if (checkCancelledStatus(Invoice::class, $invoiceId)) {
            throw new UnprocessableEntityHttpException(
                'Invoice is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::deletePriceModifier($companyId, $projectId, $invoiceId, $priceModifierId);
    }

  /**
   * Export data from invoice
   * @param ExportGetRequest $request
   * @param $company_id
   * @param $project_id
   * @param $invoice_id
   * @param $format
   */

    public function export(ExportGetRequest $request, $company_id, $project_id, $invoice_id, $template_id, $format)
    {
        if (!$project = Project::find($project_id)) {
            throw new ModelNotFoundException();
        }

        if (!$invoice = $project->invoices()->find($invoice_id)) {
            throw new ModelNotFoundException();
        }

        if (!$invoice->legal_entity_id) {
            throw new UnprocessableEntityHttpException('Legal entity is not set yet.');
        }
        return (new InvoiceExporter())->export($invoice, $template_id, $format);
    }

  /**
   * Export all invoices to excel
   *
   * @param string $companyId
   * @param InvoicesGetRequest $request
   * @return BinaryFileResponse
   */
    public function exportDataTable(string $companyId, InvoicesGetRequest $request): BinaryFileResponse
    {
        $amount = Invoice::where('type', InvoiceType::accrec()->getIndex())->count();
        $request->merge([
        'amount' => $amount,
        'page' => 0,
        ]);

        $tablePreferenceType = TablePreferenceType::invoices();

        if (!empty($request->project)) {
            $tablePreferenceType = TablePreferenceType::project_invoices();
        }

        $tablePrefService = App::make(TablePreferenceService::class);
        $tablePrefs = $tablePrefService->getPreferences('users', $tablePreferenceType->getIndex())->getData();
        $columns = $tablePrefs->columns;
        $invoices = parent::getAll($companyId, $request, $tablePreferenceType->getIndex());
        $export = new DataTablesExport($invoices['data'], $columns);

        return Excel::download($export, 'invoices_' . date('Y-m-d') . '.xlsx');
    }

  /**
   * Get all invoices belonging to a project
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

        return parent::getAll($companyId, $request, TablePreferenceType::project_invoices()->getIndex());
    }

    /**
   * Get all invoices belonging to a project
   * @param string $companyId
   * @param string $projectId
   * @param string $invoiceId
   * @return JsonResponse
   * @throws UnauthorizedException
   */
    public function getEmailTemplate(string $companyId, string $projectId, string $invoiceId, Request $request): JsonResponse
    {
        if (UserRole::isSales(auth()->user()->role) || UserRole::isHr(auth()->user()->role)) {
            throw new UnauthorizedException();
        }
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($projectId);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        $invoice = parent::getSingleFromProject($companyId, $projectId, $invoiceId);
        if (!empty($invoice->emailTemplate)) {
            return EmailTemplateResource::make($invoice->emailTemplate)->toResponse($request);
        }

        return response()->json(false);
    }

    /**
   * Toggle send renminders to client status
   * @param string $companyId
   * @param string $projectId
   * @param string $invoiceId
   * @return JsonResponse
   * @throws UnauthorizedException
   */
    public function toggleSendingRemindersStatus(string $companyId, string $projectId, string $invoiceId)
    {
        $status = $this->invoiceService->toggleSendingRemindersStatus($invoiceId);
        return response()->json($status);
    }
}
