<?php

namespace App\Utils;

use App\Enums\NotificationReminderType;
use App\Mail\InvoiceFriendlyReminder;
use App\Mail\InvoiceLessFriendlyReminder;
use App\Models\Company;
use App\Models\EmailReminder;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class RemindersUtils
{

    /**
     * Create a new reminders utils instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the default reminders configurations
     */
    public function defaultReminders()
    {
        return [
            ['type' => NotificationReminderType::overdue_by()->getIndex(), 'value' => 2],
            ['type' => NotificationReminderType::due_in()->getIndex(), 'value' => 1],
            ['type' => NotificationReminderType::due_in()->getIndex(), 'value' => 6],
            ['type' => NotificationReminderType::due_in()->getIndex(), 'value' => 16],
        ];
    }

    /**
     * Checks if the invoice is due in a given days
     * @param Invoice $invoice
     * @param string $reminderType
     * @param int $reminderDays
     */
    public function isDueIn(Invoice $invoice, int $reminderType, int $reminderDays)
    {
        return Carbon::now()->toDateString() == Carbon::parse($invoice->due_date)->subDays($reminderDays)->toDateString() &&
            NotificationReminderType::due_in()->getIndex() == $reminderType;
    }

    /**
     * Checks if the invoice is overdue by a given days
     * @param Invoice $invoice
     * @param string $reminderType
     * @param int $reminderDays
     */
    public function isOverdueBy(Invoice $invoice, int $reminderType, int $reminderDays)
    {
        return Carbon::now()->toDateString() == Carbon::parse($invoice->due_date)->addDays($reminderDays)->toDateString() &&
            NotificationReminderType::isOverdue_by($reminderType);
    }

    /**
     * Notifies by given email address or cc addresses if invoice is due in a certain day
     * @param Company $company
     * @param Invoice $invoice
     * @param array $ccMail
     * @param int $day
     */
    public function notifyIfInvoiceIsDueIn(Company $company, Invoice $invoice, array $to, array $ccMail, int $day, $reminderId = null)
    {
        Mail::to($to)
            ->cc($this->getCcAddresses($invoice, $ccMail))
            ->queue(new InvoiceLessFriendlyReminder($company->id, $invoice->id, $day, $reminderId));
    }

    /**
     * Notifies by given email address or cc addresses if invoice is overdue by a certain day
     * @param Company $company
     * @param Invoice $invoice
     * @param array $ccMail
     * @param int $day
     */
    public function notifyIfInvoiceIsOverdueBy(Company $company, Invoice $invoice, array $to, array $ccMail, int $day, $reminderId = null)
    {
        Mail::to($to)
            ->cc($this->getCcAddresses($invoice, $ccMail))
            ->queue(new InvoiceFriendlyReminder($company->id, $invoice->id, $day, $reminderId));
    }

    /**
     * @param Invoice $invoice
     * @param $reminderId
     */
    public static function getOverdueEmailDesign(Invoice $invoice, $reminderId = null)
    {
        $reminder = EmailReminder::find($reminderId);
        $designTemplate = $reminder->designTemplate ?? null;
        if (empty($designTemplate)) {
            $emailTemplate = EmailUtils::getEntityEmailTemplate($invoice);
            if (!empty($emailTemplate)) {
                $designTemplate = $emailTemplate->reminders()->overdue_by()->first();
            }
        }
        return $designTemplate;
    }

    /**
     * @param Invoice $invoice
     * @param $reminderId
     */
    public static function getDueInEmailDesign(Invoice $invoice, $reminderId = null)
    {
        $reminder = EmailReminder::find($reminderId);
        $designTemplate = $reminder->designTemplate ?? null;
        if (empty($designTemplate)) {
            $emailTemplate = EmailUtils::getEntityEmailTemplate($invoice);
            if (!empty($emailTemplate)) {
                $designTemplate = $emailTemplate->reminders()->due_in()->first();
            }
        }
        return $designTemplate;
    }

    /**
     * @param Invoice $invoice
     * @param array $defaultCc
     * @return array
     */
    private function getCcAddresses(Invoice $invoice, array $defaultCc = []): array
    {
        $cc = collect($defaultCc);

        if (!empty($invoice->emailTemplate->cc_addresses)) {
            $cc = $cc->merge($invoice->emailTemplate->cc_addresses);
        }

        $cc = $cc->filter();

        return $cc->toArray();
    }
}
