<?php

namespace App\Services;

use App\Contracts\Repositories\CompanySettingRepositoryInterface;
use App\DTO\Settings\CompanySettingDTO;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Crypt;

class CompanySettingsService
{
    protected CompanySettingRepositoryInterface $companySettingRepository;

    public function __construct(
        CompanySettingRepositoryInterface $companySettingRepository
    ) {
        $this->companySettingRepository = $companySettingRepository;
    }

    public function view(string $companyId): CompanySetting
    {
        $setings =  $this->companySettingRepository->firstByIdOrNull($companyId);

        if (empty($setings)) {
            $setings = new CompanySetting;
            $setings->fill((new CompanySettingDTO())->toArray());
        }

        if (!empty($companyId) && !$setings->exists()) {
            $setings->company_id = $companyId;
            $setings->save();
        }

        return $setings;
    }

    /**
     * Create company settings if not exists
     * @param string $companyId
     * @param CompanySettingDTO $companySettingDTO
     * @return CompanySetting
     */
    public function updateOrCreate(string $companyId, CompanySettingDTO $companySettingDTO): CompanySetting
    {
        // Filtering not null value
        $requestSettingsData = [];
        foreach ($companySettingDTO as $settingName => $settingValue) {
            if ($settingValue !== null) {
                $requestSettingsData[$settingName] = $settingValue;
            }
        }

        $companySetting = $this->companySettingRepository->firstByIdOrNull($companyId);

        if (!$companySetting) {
            $requestSettingsData['company_id'] = $companyId;
            $this->companySettingRepository->create($requestSettingsData);
        } else {
            $this->companySettingRepository->update($companySetting->company_id, $requestSettingsData);
        }

        return $this->companySettingRepository->firstById($companyId);
    }
}
