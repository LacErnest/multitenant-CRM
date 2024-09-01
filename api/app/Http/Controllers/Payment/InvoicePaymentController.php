<?php

namespace App\Http\Controllers\Payment;

use App\DTO\InvoicePayments\CreateInvoicePaymentDTO;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\TablePreferenceType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\InvoicePaymentCreateRequest;
use App\Http\Requests\Payment\InvoicePaymentUpdateRequest;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Http\Resources\Payment\InvoicePaymentResource;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Project;
use App\Services\InvoicePaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class InvoicePaymentController extends Controller
{
    protected string $model = InvoicePayment::class;

    protected InvoicePaymentService $invoicePaymentService;

    public function __construct(InvoicePaymentService $invoicePaymentService)
    {
        $this->invoicePaymentService = $invoicePaymentService;
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
            return parent::getAll($companyId, $request, TablePreferenceType::invoice_payments()->getIndex());
        }
        throw new UnauthorizedException();
    }

    /**
     * Get one payment of a specific invoice
     * @param $company_id
     * @param $project_id
     * @return JsonResponse
     */
    public function getAllFromProject(Request $request, $companyId, $projectId)
    {

        $request['project'] = $projectId;

        return parent::getAll($companyId, $request, TablePreferenceType::invoice_payments()->getIndex());
    }

    /**
     * Get one payment of a specific invoice
     * @param $company_id
     * @param $invoice_id
     * @param $invoice_payment_id
     * @return JsonResponse
     */
    public function getSingleFromInvoice(
        string $companyId,
        string $projectId,
        string $invoiceId,
        string $invoicePaymentId
    ) {
        if (UserRole::isSales(auth()->user()->role) || UserRole::isHr(auth()->user()->role)) {
            throw new UnauthorizedException();
        }
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $invoice = Invoice::findOrFail($invoiceId);
            if ($invoice->project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }
        $invoicePayment = InvoicePayment::where('invoice_id', $invoiceId)->find($invoicePaymentId);
        if ($invoicePayment) {
            return InvoicePaymentResource::make($invoicePayment);
        } else {
            throw new ModelNotFoundException();
        }
    }

    /**
     * Create an payment for an invoice
     * @param string $invoiceId
     * @param InvoicePaymentCreateRequest $invoicePaymentCreateRequest
     * @return JsonResponse
     */
    public function createInvoicePayment(
        InvoicePaymentCreateRequest $invoicePaymentCreateRequest,
        string $companyId,
        string $projectId,
        string $invoiceId
    ): JsonResponse {
        $invoice = Invoice::find($invoiceId);
        if ($invoice) {
            try {
                $this->invoicePaymentService->checkAuthorization();
                $this->invoicePaymentService->checkInvoiceStatus($invoiceId);
            } catch (UnprocessableEntityHttpException $exception) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }
            $invoicePaymentDTO = new CreateInvoicePaymentDTO($invoicePaymentCreateRequest->allSafe());
            $invoicePayment = $this->invoicePaymentService->create($invoice, $invoicePaymentDTO);
            return response()->json([
            'message' => 'invoice payment created successfully.',
            'payment' => InvoicePaymentResource::make($invoicePayment),
            'invoice' => InvoiceResource::make($invoice)
            ]);
        }
        throw new ModelNotFoundException();
    }

    /**
     * Update an invoice for a project
     * @param $company_id
     * @param $invoice_id
     * @param $invoice_payment_id
     * @param InvoicePaymentUpdateRequest $request
     * @return mixed
     */
    public function updateInvoicePayment(
        InvoicePaymentUpdateRequest $request,
        string $companyId,
        string $projectId,
        string $invoiceId,
        string $invoicePaymentId
    ) {
        $invoicePayment = InvoicePayment::findOrFail($invoicePaymentId);
        if ($invoicePayment->invoice_id === $invoiceId) {
            try {
                $this->invoicePaymentService->checkAuthorization();
                $this->invoicePaymentService->checkInvoiceStatus($invoiceId);
            } catch (UnprocessableEntityHttpException $exception) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }
            $invoicePayment = $this->invoicePaymentService->update($invoicePayment, $request);
            return response()->json([
            'message' => 'invoice payment updated successfully.',
            'payment' => InvoicePaymentResource::make($invoicePayment),
            'invoice' => InvoiceResource::make(Invoice::find($invoiceId))
            ]);
        }
        throw new ModelNotFoundException();
    }

    /**
     * Get all payments belonging to a invoices
     * @param string $companyId
     * @param string $invoiceId
     * @param Request $request
     * @param null $entity
     * @return array
     */
    public function getAllFromInvoice(string $companyId, string $projectId, string $invoiceId, Request $request, $entity = null): array
    {
        $request['invoice'] = $invoiceId;

        $project = Invoice::findOrFail($invoiceId)->project;
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }


        return parent::getAll($companyId, $request, TablePreferenceType::project_invoices()->getIndex());
    }

    /**
     * Get all payments belonging to a invoices
     * @param string $companyId
     * @param string $projectId
     * @param string $invoiceId
     * @param Request $request
     * @param null $entity
     * @return array
     */
    public function getTotalPaidAmountForInvoice(string $companyId, string $projectId, string $invoiceId): array
    {

        if (!UserRole::isPm_restricted(auth()->user()->role)) {
            $invoice = Invoice::findOrFail($invoiceId);
            return [
            'invoice' => InvoiceResource::make($invoice),
            'paid_amount' => $this->invoicePaymentService->getTotalPaidAmountForInvoice($invoiceId)
            ];
        }

        throw new UnauthorizedException();
    }

    /**
     * Delete a price modifier for an invoice
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $invoiceId
     * @param string $invoicePaymentIds
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletePaymentInvoices(
        string $companyId,
        string $projectId,
        string $invoiceId,
        Request $request
    ): JsonResponse {
        try {
            $this->invoicePaymentService->checkAuthorization();
            $this->invoicePaymentService->checkInvoiceStatus($invoiceId);
        } catch (UnprocessableEntityHttpException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
        $invoice = Invoice::findOrFail($invoiceId);
        $status = $this->invoicePaymentService->deteleInvoicePayment($invoice, $request->all());
        return response()->json([
          'message' => $status . ' invoice payment(s) deleted successfully.',
          'invoice' => InvoiceResource::make($invoice)
        ]);
    }
}
