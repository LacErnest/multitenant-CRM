<?php

namespace App\Http\Controllers\Settings;

use App\DTO\Settings\CompanySettingDTO;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CompanySettingUpdateRequest;
use App\Http\Resources\Settings\CompanySettingsResource;
use App\Services\CompanySettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Http\Response;

class CompanySettingController extends Controller
{
    protected CompanySettingsService $companySettingService;

    public function __construct(CompanySettingsService $companySettingService)
    {
        $this->companySettingService = $companySettingService;
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
        $setting = $this->companySettingService->view($companyId);

        return CompanySettingsResource::make($setting)->toResponse($request);
    }

    /**
     * Update the document numbers setting of a specific legal entity
     *
     * @param string $companyId
     * @param CompanySettingUpdateRequest $companySettingUpdateRequest
     * @return JsonResponse
     */
    public function update(
        string $companyId,
        CompanySettingUpdateRequest $companySettingUpdateRequest
    ): JsonResponse {

        if (!auth()->user()->super_user) {
            throw new UnauthorizedException('unwarranted', Response::HTTP_FORBIDDEN);
        }

        $settingDTO = new CompanySettingDTO($companySettingUpdateRequest->allSafe());
        $setting = $this->companySettingService->updateOrCreate($companyId, $settingDTO);
        return CompanySettingsResource::make($setting)->toResponse($companySettingUpdateRequest);
    }
}
