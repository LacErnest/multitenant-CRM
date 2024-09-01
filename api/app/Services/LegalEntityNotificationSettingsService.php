<?php

namespace App\Services;

use App\Contracts\Repositories\LegalEntityNotificationSettingRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\DTO\LegalEntities\LegalEntityNotificationSettingDTO;
use App\Models\LegalEntityNotificationSetting;


class LegalEntityNotificationSettingsService
{
    protected LegalEntityNotificationSettingRepositoryInterface $legalEntityNotificationSettingRepository;

    protected LegalEntityRepositoryInterface $legalEntityRepository;

    public function __construct(
        LegalEntityNotificationSettingRepositoryInterface $legalEntityNotificationSettingRepository
    ) {
        $this->legalEntityNotificationSettingRepository = $legalEntityNotificationSettingRepository;
    }

    public function view(string $legalEntityId): ?LegalEntityNotificationSetting
    {
        return $this->legalEntityNotificationSettingRepository->firstByIdOrNull($legalEntityId) ?? new LegalEntityNotificationSetting;
    }

    public function update(string $legalEntityId, LegalEntityNotificationSettingDTO $settingDTO): ?LegalEntityNotificationSetting
    {
        if ($settingDTO->enable_submited_invoice_notification) {
            $this->legalEntityNotificationSettingRepository->update($legalEntityId, $settingDTO->toArray());
        } else {
            $this->legalEntityNotificationSettingRepository->update($legalEntityId, [
            'enable_submited_invoice_notification' => $settingDTO->enable_submited_invoice_notification
            ]);
        }

        return $this->view($legalEntityId);
    }


    public function create(string $legalEntityId, array $payload): LegalEntityNotificationSetting
    {
        $payload['legal_entity_id'] = $legalEntityId;

        return $this->legalEntityNotificationSettingRepository->create($payload);
    }
}
