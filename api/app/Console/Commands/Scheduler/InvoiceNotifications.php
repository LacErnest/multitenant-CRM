<?php

namespace App\Console\Commands\Scheduler;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\CompanyNotificationSetting;
use App\Models\Invoice;
use App\Models\User;
use App\Utils\RemindersUtils;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Tenancy\Facades\Tenancy;

class InvoiceNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:invoice_notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify customers and accountants for overdue invoices';

    /**
     * @var RemindersUtils
     */
    private $remindersUtils;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->remindersUtils = new RemindersUtils;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $companies = Company::all();
        $companies->each(function ($company) {
            Tenancy::setTenant($company);
            $this->sendRemindersByCompany($company);
        });
        return 0;
    }

    /**
     * @param Company $company
     * @return void
     */
    private function sendRemindersByCompany(Company $company): void
    {
        $this->line('>>>>>company founded:' . $company->name);

        $companyNotificationSettings = CompanyNotificationSetting::find($company->id);
        $emailTemplateGloballyDisabled = $companyNotificationSettings->globally_disabled_email ?? false;

        if (!$emailTemplateGloballyDisabled) {
            $defaultCcAddresses = $this->getDefaultCcAddresses($company);

            $invoices = $this->findInvoicesReadyForReminders();

            foreach ($invoices as $invoice) {
                $this->sendRemindersForAnInvoice($company, $invoice, $defaultCcAddresses);
            }
        }
    }

    /**
     * send reminders for a given reminder according to an invoice
     * @param Company $company
     * @param Invoice $invoice
     * @param array $ccAddresses
     * @return void
     */
    private function sendRemindersForAnInvoice(Company $company, Invoice $invoice, SupportCollection $defaultCcAddresses)
    {
        $this->line('>>>>>>>>invoice founded:' . $invoice->number);
        $reminders = $this->getInvoiceReminders($invoice);
        $ccAddresses = $this->getAllCcAddressesForTheReminder($invoice, $defaultCcAddresses);
        $toAddresses = $this->getAllToAddresses($invoice, []);
        foreach ($reminders as $reminder) {
            $this->sendReminderForAnInvoice($company, $invoice, $reminder, $toAddresses, $ccAddresses);
        }
    }

    /**
     * send reminder for a given reminder according to an invoice
     * @param Company $company
     * @param Invoice $invoice
     * @param array $reminder
     * @param array $ccAddresses
     * @return void
     */
    private function sendReminderForAnInvoice(Company $company, Invoice $invoice, array $reminder, array $toAddresses, array $ccAddresses): void
    {
        if ($this->remindersUtils->isDueIn($invoice, $reminder['type'], $reminder['value'])) {
            $this->line('>>>>>>>>>>> reminder due in ' . $reminder['value'] . ' for invoice ' . $invoice->number . ' submitted with emailTemplate:' . ($invoice->emailTemplate->title ?? 'no template'));
            $this->remindersUtils->notifyIfInvoiceIsDueIn($company, $invoice, $toAddresses, $ccAddresses, $reminder['value'], $reminder['id'] ?? null);
        } elseif ($this->remindersUtils->isOverdueBy($invoice, $reminder['type'], $reminder['value'])) {
            $this->line('>>>>>>>>>>> reminder overdue by ' . $reminder['value'] . ' for invoice ' . $invoice->number . ' submitted with emailTemplate:' . ($invoice->emailTemplate->title ?? 'no template'));
            $this->remindersUtils->notifyIfInvoiceIsOverdueBy($company, $invoice, $toAddresses, $ccAddresses, $reminder['value'], $reminder['id'] ?? null);
        }
    }

    /**
     * Get reminders for a given invoice
     * @param Invoice $invoice
     * @return  array
     */
    private function getInvoiceReminders(Invoice $invoice): array
    {
        $reminders = $this->remindersUtils->defaultReminders();
        if (!empty($invoice->emailTemplate)) {
            $reminders = $invoice->emailTemplate->reminders->toArray();
        }
        return $reminders;
    }

    /**
     * Get default cc addresses
     * @param Company $company
     * @return SupportCollection
     */
    private function getDefaultCcAddresses(Company $company): SupportCollection
    {
        return User::where('company_id', $company->id)
            ->whereIn('role', [UserRole::owner()->getIndex(), UserRole::accountant()->getIndex()])
            ->pluck('email');
    }

    /**
     * Get all cc addresses for a given invoice
     * @param Invoice $invoice
     * @param Collection $ccAddresses
     * @return array
     */
    private function getAllCcAddressesForTheReminder(Invoice $invoice, SupportCollection $ccAddresses): array
    {
        $templateCcEmail = collect();
        if (!empty($invoice->emailTemplate)) {
            $templateCcEmail = collect($invoice->emailTemplate->cc_addresses);
        }
        return $templateCcEmail->merge($ccAddresses)->unique()->toArray();
    }

    /**
     * Find all invoices ready for reminders for the current tenant
     * @return Collection
     */
    private function findInvoicesReadyForReminders(): Collection
    {
        /**
         * @var Collection
         */
        return Invoice::with('project.salesPersons', 'project.leadGens', 'emailTemplate.reminders')->where([
            ['status', InvoiceStatus::submitted()->getIndex()],
            ['type', InvoiceType::accrec()->getIndex()],
            ['shadow', false],
            ['send_client_reminders', true]
        ])->get();
    }

    /**
     * @param Invoice $invoice
     * @param array $defaultTo
     * @return array
     */
    private function getAllToAddresses(Invoice $invoice, array $defaultTo = []): array
    {
        $cc = collect($defaultTo);
        $cc = $cc->merge(
            $invoice->project->salesPersons
                ->filter(fn($salesPerson) => !empty($salesPerson->email))
                ->pluck('email')
        );
        $cc = $cc->merge(
            $invoice->project->leadGens
                ->filter(fn($leadGen) => !empty($leadGen->email))
                ->pluck('email')
        );
        $cc = $cc->filter();
        return $cc->toArray();
    }
}
