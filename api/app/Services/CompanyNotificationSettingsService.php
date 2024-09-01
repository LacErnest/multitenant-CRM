<?php

namespace App\Services;

use App\Contracts\Repositories\CompanyNotificationSettingRepositoryInterface;
use App\DTO\LegalEntities\CompanyNotificationSettingDTO;
use App\Models\CompanyNotificationSetting;


class CompanyNotificationSettingsService
{
    protected CompanyNotificationSettingRepositoryInterface $companyNotificationSettingRepository;

    public function __construct(
        CompanyNotificationSettingRepositoryInterface $companyNotificationSettingRepository
    ) {
        $this->companyNotificationSettingRepository = $companyNotificationSettingRepository;
    }

    public function view(string $companyId): CompanyNotificationSetting
    {
        return $this->companyNotificationSettingRepository->firstByOrNull('company_id', $companyId) ?? new CompanyNotificationSetting;
    }

    public function update(string $companyId, CompanyNotificationSettingDTO $settingDTO): CompanyNotificationSetting
    {
        $this->companyNotificationSettingRepository->update($companyId, $settingDTO->toArray());
        return $this->view($companyId);
    }


    public function create(string $companyId, array $payload): CompanyNotificationSetting
    {
        $payload['company_id'] = $companyId;

        $this->companyNotificationSettingRepository->create($payload);

        return $this->view($companyId);
    }

    public function toggleCompanyEmailNotification(string $companyId): CompanyNotificationSetting
    {
        $notificationSetting = $this->view($companyId);
        if (empty($notificationSetting->company_id)) {
            $payload = [
                'company_id' => $companyId,
                'cc_addresses' => [],
                'from_address' => '',
                'from_name' => '',
                'invoice_submitted_body' => ''
            ];
            $notificationSetting = $this->companyNotificationSettingRepository->create($payload);
        }

        $notificationSetting->globally_disabled_email = !($notificationSetting->globally_disabled_email ?? false);

        $notificationSetting->save();

        return $notificationSetting;
    }
}
