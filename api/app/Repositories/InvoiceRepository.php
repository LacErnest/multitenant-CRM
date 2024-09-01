<?php


namespace App\Repositories;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Events\InvoiceSubmitted;
use App\Models\Company;
use App\Models\CompanyNotificationSetting;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Project;
use App\Models\Setting;
use App\Services\CommissionService;
use App\Services\EmailTemplateService;
use App\Services\ItemService;
use App\Services\ResourceService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tenancy\Environment;
use Tenancy\Facades\Tenancy;

/**
 * Class InvoiceRepository
 *
 * @deprecated
 */
class InvoiceRepository
{
    protected Invoice $invoice;
    /**
     * @var EmailTemplateService
     */
    private EmailTemplateService $emailTemplateService;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->emailTemplateService = App(EmailTemplateService::class);
    }

    public function create($project_id, $attributes)
    {
        $project = Project::with('order')->findOrFail($project_id);
        $defaultEmailTemplate = $this->emailTemplateService->getDefaultEmailTemplate();

        $attributes['project_id'] = $project_id;
        $attributes['order_id'] = $project->order ? $project->order->id : null;
        $attributes['created_by'] = auth()->user()->id;
        $attributes['email_template_id'] = $defaultEmailTemplate->id ?? null;
        $attributes['type'] = InvoiceType::accrec()->getIndex();

        if (!empty($project->contact->customer->payment_due_date)) {
            $currentDate = Carbon::now();
            $attributes['payment_terms'] = $currentDate->addDays($project->contact->customer->payment_due_date);
            $attributes['due_date'] = $currentDate->addDays($project->contact->customer->payment_due_date);
        }

        $format = Setting::first();
        $attributes['number'] = transformFormat($format->invoice_number_format, $format->invoice_number + 1);
        $invoice = $this->invoice->create($attributes);
        $format->invoice_number += 1;
        $format->save();
        return $invoice;
    }

    public function update(Invoice $invoice, $attributes, $model_attributes = [])
    {
        $oldDate = $invoice->date;
        $legalEntityId = $invoice->legal_entity_id;

        if (array_key_exists('pay_date', $attributes) && array_key_exists('status', $attributes)) {
            if (!InvoiceStatus::isPaid($attributes['status'])) {
                unset($attributes['pay_date']);
            }
        }

        if (Arr::exists($attributes, 'legal_entity_id') && !($attributes['legal_entity_id'] === null)) {
            if (Str::startsWith($invoice->number, 'DRAFT-')) {
                $format = getSettingsFormat($attributes['legal_entity_id']);
                if (InvoiceType::isAccrec($invoice->type)) {
                    $attributes['number'] = transformFormat($format->invoice_number_format, $format->invoice_number + 1);
                    $format->invoice_number += 1;
                } else {
                    $attributes['number'] = transformFormat($format->resource_invoice_number_format, $format->resource_invoice_number + 1);
                    $format->resource_invoice_number += 1;
                }

                $format->save();
            }
        }

        if ($this->checkIfGoBackToDraft($attributes)) {
            $attributes['close_date'] = null;
            $attributes['total_paid'] = 0;
            $attributes['submitted_date'] = null;
            $attributes['customer_notified_at'] = null;
        }


        $invoice->update($attributes, $model_attributes);

        if ((Arr::exists($attributes, 'date') && !(strtotime($attributes['date']) == strtotime($oldDate))) ||
            (Arr::exists($attributes, 'legal_entity_id') && !($attributes['legal_entity_id'] == $legalEntityId))
        ) {
            if (InvoiceType::isAccrec($invoice->type)) {
                $itemService = App::make(ItemService::class);
                $itemService->savePricesAndCurrencyRates(getTenantWithConnection(), $invoice->id, Invoice::class);
            } else {
                $resourceService = App::make(ResourceService::class);
                $resource = $resourceService->findBorrowedResource($invoice->purchaseOrder->resource_id);
                if ($invoice->legalEntity->address->country == $resource->country) {
                    $taxRate = getTaxRate($invoice->date, $invoice->legal_entity_id) / 100;
                    $invoice->manual_vat = round($invoice->manual_price * $taxRate, 2);
                    $invoice->manual_price += $invoice->manual_vat;
                    $invoice->total_vat = round($invoice->total_price * $taxRate, 2);
                    $invoice->total_price += $invoice->total_vat;
                    $invoice->total_vat_usd = round($invoice->total_price_usd * $taxRate, 2);
                    $invoice->total_price_usd += $invoice->total_vat_usd;
                    $invoice->save();
                }
            }
        }

        if (Arr::exists($attributes, 'status') && InvoiceStatus::isPaid($attributes['status']) && empty($invoice->close_date)) {
            $invoice->update(['close_date' => $invoice->updated_at]);
        }

        if ($invoice->master) {
            $company = Company::find(getTenantWithConnection());
            $attributes['number'] = $invoice->number;
            $attributes['master'] = false;
            $attributes['close_date'] = $invoice->close_date;
            $shadowInvoices = $invoice->shadows;
            foreach ($shadowInvoices as $shadow) {
                Tenancy::setTenant(Company::find($shadow->shadow_company_id));
                $shadowInvoice = Invoice::find($shadow->shadow_id);
                $shadowInvoice->update($attributes, $model_attributes);
                if ((Arr::exists($attributes, 'date') && !(strtotime($attributes['date']) == strtotime($oldDate))) ||
                    (Arr::exists($attributes, 'legal_entity_id') && !($attributes['legal_entity_id'] == $legalEntityId))
                ) {
                    $itemService = App::make(ItemService::class);
                    $itemService->savePricesAndCurrencyRates($shadow->shadow_company_id, $shadowInvoice->id, Invoice::class);
                }
            }
            Tenancy::setTenant($company);
        }

        if ($this->checkIfGoBackToDraft($attributes)) {
            $this->clearInvoiceDependenciesOnGoBackDraft($invoice);
        }

        $this->triggerSubmittedInvoiceAndSendNotification($invoice, $attributes);



        return $invoice->refresh();
    }

    private function triggerSubmittedInvoiceAndSendNotification(Invoice $invoice, $attributes)
    {
        if (array_key_exists('status', $attributes) && InvoiceStatus::isSubmitted($attributes['status']) && empty($invoice->submitted_date)) {
            $companyNotificationSettings = CompanyNotificationSetting::find(getTenantWithConnection());
            $emailTemplateGloballyDisabled = $companyNotificationSettings->globally_disabled_email ?? false;
            if (!$emailTemplateGloballyDisabled && isset($attributes['notify_client']) && $attributes['notify_client'] == true) {
                event(new InvoiceSubmitted($invoice));
            }
        }
    }

    public function createFromXero($invoiceXero, $tenant_id)
    {
        if ($invoiceXero->getType() != InvoiceType::accpay()->getName()) {
            return null;
        }

        $company     = Company::where('tenant_id', $tenant_id)->first();
        $environment = app(Environment::class);
        $environment->setTenant($company);

        $invoiceModel = $this->invoice->create([
            'xero_id'     => $invoiceXero->getInvoiceId(),
            'type'        => InvoiceType::make($invoiceXero->getType())->getIndex(),
            'date'        => $this->convertStringToDate($invoiceXero->getDate()),
            'due_date'    => $this->convertStringToDate($invoiceXero->getDueDate()),
            'status'      => InvoiceStatus::make($invoiceXero->getStatus())->getIndex(),
            'reference'   => $invoiceXero->getReference(),
            'number'      => $invoiceXero->getInvoiceNumber(),
            'total_price' => $invoiceXero->getTotal()
        ]);

        return $invoiceModel;
    }

    public function updateFromXero($invoiceXero, $tenant_id)
    {
        if ($invoiceXero->getType() != InvoiceType::accpay()->getName()) {
            return null;
        }

        $company     = Company::where('tenant_id', $tenant_id)->first();
        $environment = app(Environment::class);
        $environment->setTenant($company);

        $invoiceModel = $this->invoice->where('xero_id', $invoiceXero->getInvoiceId())->first();

        if (!$invoiceModel) {
            return null;
        }

        if ($invoiceXero->getStatus() == InvoiceStatus::deleted()->getName()) {
            return $invoiceModel->delete();
        }

        $invoiceModel->update([
            'xero_id'     => $invoiceXero->getInvoiceId(),
            'type'        => InvoiceType::make($invoiceXero->getType())->getIndex(),
            'date'        => $this->convertStringToDate($invoiceXero->getDate()),
            'due_date'    => $this->convertStringToDate($invoiceXero->getDueDate()),
            'status'      => InvoiceStatus::make($invoiceXero->getStatus())->getIndex(),
            'reference'   => $invoiceXero->getReference(),
            'number'      => $invoiceXero->getInvoiceNumber(),
            'total_price' => $invoiceXero->getTotal()
        ]);

        return $invoiceModel;
    }

    public static function convertStringToDate($data)
    {

        // convert Microsfot .NET JSON Date format into native PHP DateTime()
        $match = preg_match('/([\d]{11})/', $data, $date);
        if ($match) {
            $seconds = $date[1] / 1000;
        }
        $match = preg_match('/([\d]{12})/', $data, $date);
        if ($match) {
            $seconds = $date[1] / 1000;
        }
        $match = preg_match('/([\d]{13})/', $data, $date);
        if ($match) {
            $seconds = $date[1] / 1000;
        }

        $dateString = date('d-m-Y', $seconds);
        $dateFormat = new \DateTime($dateString);
        return $dateFormat;
    }

    /**
     * Clear all invoice payment documents if invoice status go back to draft
     * @param Invoice $invoice
     * @return void
     */
    private function clearInvoicePayments(Invoice $invoice): void
    {
        foreach ($invoice->payments as $payment) {
            $payment->delete();
        }
    }

    /**
     * Clear all invoice dependencies when go back to draft status
     * @param array $attributes
     * @return bool
     */
    private function checkIfGoBackToDraft(array $attributes): bool
    {
        if (array_key_exists('status', $attributes)) {
            $nextStatus = $attributes['status'];
            if (InvoiceStatus::isDraft($nextStatus)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Clear all invoice dependencies when go back to draft status
     * @param Invoice $invoice
     * @return void
     */
    private function clearInvoiceDependenciesOnGoBackDraft(Invoice $invoice): void
    {
        $this->clearInvoicePayments($invoice);
        $this->removeCommissionPurcentages($invoice);
        $this->removeLeadGeneration($invoice);
    }

    /**
     * Remove commission percentage for invoice
     * @param Invoice $invoice
     * @return void
     */
    private function removeCommissionPurcentages(Invoice $invoice): void
    {
        /** @var CommissionService */
        $commissionService = App::make(CommissionService::class);
        $commissionService->removeCommissionPercentagesForInvoice($invoice);
    }

    /**
     * Remove lead genations for the invoice customer
     * @param Invoice $invoice
     * @return void
     */
    private function removeLeadGeneration($invoice): void
    {
        $commissionService = App::make(CommissionService::class);
        $commissionService->removeLeadGenerationCustomer(
            $invoice->project->contact->customer_id,
            $invoice->project_id,
            $invoice->id
        );
    }
}
