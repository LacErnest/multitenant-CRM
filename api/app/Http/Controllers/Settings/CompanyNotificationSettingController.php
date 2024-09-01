<?php

namespace App\Http\Controllers\Settings;

use App\DTO\LegalEntities\CompanyNotificationSettingDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CompanyNotificationSettingCreateRequest;
use App\Http\Requests\Settings\CompanyNotificationSettingUpdateRequest;
use App\Http\Resources\LegalEntity\CompanyNotificationSettingsResource;
use App\Services\CompanyNotificationSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyNotificationSettingController extends Controller
{
    protected CompanyNotificationSettingsService $companyNotificationSettingService;

    public function __construct(CompanyNotificationSettingsService $companyNotificationSettingService)
    {
        $this->companyNotificationSettingService = $companyNotificationSettingService;
    }
    /**
     * Get the document numbers setting of a specific legal entity
     *
     *  @param string $companyId
     *  @param Request $request
     * @return JsonResponse
     */
    public function view(string $companyId, Request $request): JsonResponse
    {
        $setting = $this->companyNotificationSettingService->view($companyId);

        return CompanyNotificationSettingsResource::make($setting)->toResponse($request);
    }

    /**
     * Update the document numbers setting of a specific legal entity
     *
     * @param string $companyId
     * @param CompanyNotificationSettingCreateRequest $companyNotificationSettingUpdateRequest
     * @return JsonResponse
     */
    public function create(string $companyId, CompanyNotificationSettingCreateRequest $companyNotificationSettingUpdateRequest): JsonResponse
    {
        $settingDTO = new CompanyNotificationSettingDTO($companyNotificationSettingUpdateRequest->allSafe());
        $setting = $this->companyNotificationSettingService->create($companyId, $settingDTO->toArray());

        return CompanyNotificationSettingsResource::make($setting)->toResponse($companyNotificationSettingUpdateRequest);
    }

    /**
     * Update the document numbers setting of a specific legal entity
     *
     * @param string $companyId
     * @param CompanyNotificationSettingUpdateRequest $legalEntityNotificationSettingUpdateRequest
     * @return JsonResponse
     */
    public function update(
        string $companyId,
        CompanyNotificationSettingUpdateRequest $companyNotificationSettingUpdateRequest
    ): JsonResponse {
        $settingDTO = new CompanyNotificationSettingDTO($companyNotificationSettingUpdateRequest->allSafe());
        $setting = $this->companyNotificationSettingService->update($companyId, $settingDTO);

        return CompanyNotificationSettingsResource::make($setting)->toResponse($companyNotificationSettingUpdateRequest);
    }
}
