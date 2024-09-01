<?php


namespace App\Services;

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\DTO\Invoices\CreateInvoiceDTO;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Requests\BaseRequest;
use App\Http\Requests\Invoice\InvoiceCreateRequest;
use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class InvoiceService
{
    protected InvoiceRepository $invoice_repository;

    protected InvoiceRepositoryInterface $invoiceRepository;

    private LegalEntityRepositoryInterface $legalEntityRepository;

    private LegalEntitySettingRepositoryInterface $legalSettingRepository;

    private CommissionService $commissionService;

    public function __construct(
        InvoiceRepository $invoice_repository,
        InvoiceRepositoryInterface $invoiceRepository,
        LegalEntityRepositoryInterface $legalEntityRepository,
        LegalEntitySettingRepositoryInterface $legalSettingRepository,
        CommissionService $commissionService
    ) {
        $this->invoice_repository = $invoice_repository;
        $this->invoiceRepository = $invoiceRepository;
        $this->legalEntityRepository = $legalEntityRepository;
        $this->legalSettingRepository = $legalSettingRepository;
        $this->commissionService = $commissionService;
    }

    public function create(string $projectId, CreateInvoiceDTO $createInvoiceDTO): Invoice
    {
        $projectRepository = App::make(ProjectRepositoryInterface::class);
        $legalEntity = $this->legalEntityRepository->firstById($createInvoiceDTO->legal_entity_id);
        $format = $this->legalSettingRepository->firstById($legalEntity->legal_entity_setting_id);
        $project = $projectRepository->firstById($projectId);
        $isIntraCompany = isIntraCompany($project->contact->customer->id ?? null);

        if ($project->order->shadow) {
            throw new UnprocessableEntityHttpException('This order is a shadow of an other order. No invoices can be created.');
        }

        if (!$createInvoiceDTO->vat_status) {
            $createInvoiceDTO->vat_status = VatStatus::default()->getIndex();
        }

        $createInvoiceDTO->eligible_for_earnout = !$isIntraCompany || $createInvoiceDTO->eligible_for_earnout;

        $invoice = $this->invoiceRepository->create(
            array_merge($createInvoiceDTO->toArray(), [
                'project_id' => $projectId,
                'order_id' => $project->order ? $project->order->id : null,
                'created_by' => auth()->user()->id,
                'type' => InvoiceType::accrec()->getIndex(),
                'number' => transformFormat($format->invoice_number_format, $format->invoice_number + 1),
                'status' => InvoiceStatus::draft()->getIndex(),
                'master' => $project->order ? $project->order->master : false,
            ])
        );
        $this->legalSettingRepository->update($format->id, ['invoice_number' => $format->invoice_number + 1]);

      // When created, we just add commision percentages to sales person and second sales person
      //$this->commissionService->createCommissionPercentagesFromInvoice($invoice);

        return $invoice;
    }

    public function update(Invoice $invoice, BaseRequest $request)
    {
        if ($request->has('eligible_for_earnout')) {
            $isIntraCompany = isIntraCompany($invoice->project->contact->customer->id ?? null);
            $request->merge([
            'eligible_for_earnout' => !$isIntraCompany || $request->eligible_for_earnout
            ]);
        }
        return $this->invoice_repository->update($invoice, $request->allSafe());
    }

    public function createFromXero($invoiceXero, $tenant_id)
    {
        return $this->invoice_repository->createFromXero($invoiceXero, $tenant_id);
    }

    public function updateFromXero($invoiceXero, $tenant_id)
    {
        return $this->invoice_repository->updateFromXero($invoiceXero, $tenant_id);
    }

    public function checkAuthorization(): void
    {
        if (
          UserRole::isSales(auth()->user()->role) || UserRole::isPm(auth()->user()->role) ||
          UserRole::isHr(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role) ||
          UserRole::isPm_restricted(auth()->user()->role)
        ) {
            throw new UnauthorizedException();
        }
    }

    public function checkInvoiceTypeAndStatus(string $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);
        if (!$invoice->legal_entity_id) {
            throw new UnprocessableEntityHttpException(
                'Legal entity is not set yet.'
            );
        }
        if ($invoice->shadow) {
            throw new UnprocessableEntityHttpException(
                "Shadow invoice, can't create, update or delete items or modifiers."
            );
        }
        if (InvoiceType::isAccpay($invoice->type)) {
            throw new UnprocessableEntityHttpException(
                "Payable invoice, can't create, update or delete items or modifiers."
            );
        }
        if (InvoiceStatus::isPaid($invoice->status) || InvoiceStatus::isSubmitted($invoice->status) || InvoiceStatus::isUnpaid($invoice->status)) {
            throw new UnprocessableEntityHttpException(
                "Invoice has been submitted, can't create, update or delete items or modifiers."
            );
        }
    }

    /**
     * Toggle invoice sendings reminders notifications to the client
     * @param string $invoiceId
     * @return bool
     */
    public function toggleSendingRemindersStatus(string $invoiceId): bool
    {
        $invoice = Invoice::findOrFail($invoiceId);
        if (!InvoiceStatus::isSubmitted($invoice->status)) {
            throw new UnprocessableEntityHttpException(
                'Invalid invoice status. Invoice has not been submitted yet.'
            );
        }
        $invoice->send_client_reminders = !$invoice->send_client_reminders;
        $invoice->save();
        return $invoice->send_client_reminders;
    }
}
