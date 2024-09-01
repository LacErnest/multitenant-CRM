<?php

namespace App\Services;

use App\Contracts\Repositories\EmailTemplateRepositoryInterface;
use App\DTO\EmailTemplates\EmailTemplateDTO;
use App\Models\EmailReminder;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;

class EmailTemplateService
{
    /**
     * @var EmailTemplateRepositoryInterface
     */
    protected EmailTemplateRepositoryInterface $emailTemplateRepository;

    /**
     * @var SmtpSettingsService
     */
    protected SmtpSettingsService $smtpSettingService;

    /**
     * @var DesignTemplateService
     */
    protected DesignTemplateService $designTemplateService;

    /**
     * @var CompanyNotificationSettingsService
     */
    protected CompanyNotificationSettingsService $companyNotificationSettingsService;

    /**
     * Email template constructor
     * @param EmailTemplateRepositoryInterface $emailTemplateRepository
     * @param SmtpSettingsService $smtpSettingService
     * @param CompanyNotificationSettingsService $companyNotificationSettingsService
     */
    public function __construct(
        EmailTemplateRepositoryInterface $emailTemplateRepository,
        SmtpSettingsService $smtpSettingService,
        DesignTemplateService $designTemplateService,
        CompanyNotificationSettingsService $companyNotificationSettingsService
    ) {
        $this->emailTemplateRepository = $emailTemplateRepository;
        $this->smtpSettingService = $smtpSettingService;
        $this->designTemplateService = $designTemplateService;
        $this->companyNotificationSettingsService = $companyNotificationSettingsService;
    }

    /**
     * Get default email template
     * @return EmailTemplate | null
     */
    public function getDefaultEmailTemplate()
    {
        return EmailTemplate::default()->first();
    }

    /**
     * Set Invoice default email template
     * @return void
     */
    public function setDefaultEmailTemplate(Invoice &$invoice): void
    {
        $defaultEmailTemplate =  EmailTemplate::default()->first();
        $invoice->email_template_id = optional($defaultEmailTemplate)->id;
    }

    /**
     * Get email template document for the given id
     * @param string $id
     * @return EmailTemplate
     */
    public function findById(string $id): EmailTemplate
    {
        return $this->emailTemplateRepository->firstById($id, ['reminders']);
    }

    /**
     * Get email template document for the given id
     * Return null if no email template found
     * @param string $id
     * @return EmailTemplate | null
     */
    public function findByIdOrNull(string $id): ?EmailTemplate
    {
        return $this->emailTemplateRepository->firstByIdOrNull($id);
    }

    /**
     * Get all email template documents ordered by creation date
     * @return Collection
     */
    public function getAll(): Collection
    {
        return EmailTemplate::orderByDesc('created_at')->get();
    }

    /**
     * Create new email template document
     * @param EmailTemplateDTO $templateDTO
     * @return EmailTemplate
     */
    public function create(EmailTemplateDTO $templateDTO): EmailTemplate
    {
        $smtpSetting = $this->smtpSettingService->findById($templateDTO->sender_id);
        $emailTemplate = $smtpSetting->templates()->create($templateDTO->toArray());
        $this->saveReminders($emailTemplate, $templateDTO);
        return $emailTemplate;
    }

    /**
     * Update existing email template document
     * @param string $id
     * @param EmailTemplateDTO $templateDTO
     * @return EmailTemplate
     */
    public function update(string $id, EmailTemplateDTO $templateDTO): EmailTemplate
    {
        $smtpSetting = $this->smtpSettingService->findById($templateDTO->sender_id);
        $emailTemplate = $smtpSetting->templates()->findOrFail($id);
        $emailTemplate->update($templateDTO->toArray());
        $this->saveReminders($emailTemplate, $templateDTO);
        return $this->findById($id);
    }

    /**
     * @param string $id
     * @return EmailTemplate
     */
    public function markAsDefault(string $id): EmailTemplate
    {
        EmailTemplate::query()->update(['default' => false]);
        $this->emailTemplateRepository->update($id, ['default' => true]);
        return $this->findById($id);
    }

    /**
     * @param string $id
     */
    public function toggleGloballyDisabledStatus(string $companyId): bool
    {
        $companyNotificationSetting = $this->companyNotificationSettingsService->toggleCompanyEmailNotification($companyId);
        return $companyNotificationSetting->globally_disabled_email;
    }

    /**
     * @param string $companyId
     */
    public function getGloballyDisabledStatus(string $companyId): bool
    {
        $companyNotificationSetting = $this->companyNotificationSettingsService->view($companyId);
        return $companyNotificationSetting->globally_disabled_email ?? false;
    }

    /**
     * Delete existing email template document
     * @param string $id
     * @return int
     */
    public function delete(string $id): int
    {
        $emailTemplate = $this->emailTemplateRepository->firstById($id);
        $isConfiguredToBeUsed = Invoice::whereHas('emailTemplate', function ($query) use ($emailTemplate) {
            return $query->where('id', $emailTemplate->id);
        })->exists();

        if ($isConfiguredToBeUsed) {
            throw new \Exception('Email template cannot be deleted. Some invoices are configured to be used.');
        }

        return $emailTemplate->delete();
    }

    /**
     * Save email template reminders
     * @param EmailTemplate $emailTemplate
     * @param EmailTemplateDTO $templateDTO
     * @return void
     */
    private function saveReminders(EmailTemplate $template, EmailTemplateDTO $templateDTO)
    {
        $reminders = array_map(function ($value, $type, $template, $id) {
            $item = ['type' => $type, 'value' => $value, 'design_template_id'=> $template];
            if (!empty($id)) {
                return EmailReminder::findOrFail($id)->fill($item);
            }
            return new EmailReminder($item);
        }, $templateDTO->reminder_values, $templateDTO->reminder_types, $templateDTO->reminder_templates, $templateDTO->reminder_ids ?? []);

        $savedTemplateIds = $template->reminders()->saveMany($reminders);

        $savedTemplateIds = array_map(function ($template) {
            return $template->id;
        }, (array) $savedTemplateIds);

        $template->reminders()->whereNotIn('id', $savedTemplateIds)->delete();
    }

    public function getDesignTemplateHtmlPath(EmailTemplate $template)
    {
        return $this->designTemplateService->getDesignTemplateHtmlPath($template->designTemplate);
    }
}
