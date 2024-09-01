<?php

namespace App\Http\Controllers\Settings;

use App\DTO\LegalEntities\LegalEntityNotificationSettingDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LegalEntityNotificationSettingCreateRequest;
use App\Http\Requests\Settings\LegalEntityNotificationSettingUpdateRequest;
use App\Http\Resources\LegalEntity\LegalEntityNotificationSettingsResource;
use App\Services\LegalEntityNotificationSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegalEntityNotificationSettingController extends Controller
{
    protected LegalEntityNotificationSettingsService $legalEntityNotificationSettingService;

    public function __construct(LegalEntityNotificationSettingsService $legalEntityNotificationSettingService)
    {
        $this->legalEntityNotificationSettingService = $legalEntityNotificationSettingService;
    }
    /**
     * Get the document numbers setting of a specific legal entity
     *
     *  @param string $legalEntityId
     *  @param Request $request
     * @return JsonResponse
     */
    public function view(string $legalEntityId, Request $request): JsonResponse
    {
        $setting = $this->legalEntityNotificationSettingService->view($legalEntityId);

        return LegalEntityNotificationSettingsResource::make($setting)->toResponse($request);
    }

    /**
     * Update the document numbers setting of a specific legal entity
     *
     * @param string $legalEntityId
     * @param LegalEntityNotificationSettingCreateRequest $legalEntityNotificationSettingUpdateRequest
     * @return JsonResponse
     */
    public function create(string $legalEntityId, LegalEntityNotificationSettingCreateRequest $legalEntityNotificationSettingUpdateRequest): JsonResponse
    {
        $settingDTO = new LegalEntityNotificationSettingDTO($legalEntityNotificationSettingUpdateRequest->allSafe());
        $setting = $this->legalEntityNotificationSettingService->create($legalEntityId, $settingDTO->toArray());

        return LegalEntityNotificationSettingsResource::make($setting)->toResponse($legalEntityNotificationSettingUpdateRequest);
    }

    /**
     * Update the document numbers setting of a specific legal entity
     *
     * @param string $legalEntityId
     * @param LegalEntityNotificationSettingUpdateRequest $legalEntityNotificationSettingUpdateRequest
     * @return JsonResponse
     */
    public function update(
        string $legalEntityId,
        LegalEntityNotificationSettingUpdateRequest $legalEntityNotificationSettingUpdateRequest
    ): JsonResponse {
        $settingDTO = new LegalEntityNotificationSettingDTO($legalEntityNotificationSettingUpdateRequest->allSafe());
        $setting = $this->legalEntityNotificationSettingService->update($legalEntityId, $settingDTO);

        return LegalEntityNotificationSettingsResource::make($setting)->toResponse($legalEntityNotificationSettingUpdateRequest);
    }
}
