<?php

namespace App\Http\Controllers\Settings;

use App\DTO\LegalEntities\LegalEntitySettingDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LegalEntitySettingUpdateRequest;
use App\Http\Resources\LegalEntity\LegalEntitySettingsResource;
use App\Services\LegalEntitySettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegalEntitySettingController extends Controller
{
    protected LegalEntitySettingsService $legalEntitySettingService;

    public function __construct(LegalEntitySettingsService $legalEntitySettingService)
    {
        $this->legalEntitySettingService = $legalEntitySettingService;
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
        $setting = $this->legalEntitySettingService->view($legalEntityId);

        return LegalEntitySettingsResource::make($setting)->toResponse($request);
    }

    /**
     * Update the document numbers setting of a specific legal entity
     *
     * @param string $legalEntityId
     * @param LegalEntitySettingUpdateRequest $legalEntitySettingUpdateRequest
     * @return JsonResponse
     */
    public function update(
        string $legalEntityId,
        LegalEntitySettingUpdateRequest $legalEntitySettingUpdateRequest
    ): JsonResponse {
        $settingDTO = new LegalEntitySettingDTO($legalEntitySettingUpdateRequest->allSafe());
        $setting = $this->legalEntitySettingService->update($legalEntityId, $settingDTO);

        return LegalEntitySettingsResource::make($setting)->toResponse($legalEntitySettingUpdateRequest);
    }
}
