<?php

namespace App\Http\Controllers\Settings;

use App\DTO\Settings\SmtpSettingDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SmtpSettingCreateRequest;
use App\Http\Requests\Settings\SmtpSettingUpdateRequest;
use App\Http\Resources\Settings\SmtpSettingsResource;
use App\Services\SmtpSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmtpSettingController extends Controller
{
    /**
     * @var SmtpSettingsService
     */
    protected SmtpSettingsService $smtpSettingService;

    public function __construct(SmtpSettingsService $smtpSettingService)
    {
        $this->smtpSettingService = $smtpSettingService;
    }

    /**
     * Get the document numbers setting of a specific smtp setting
     *
     *  @param string $companyId
     *  @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $settings = $this->smtpSettingService->getAll();

        return SmtpSettingsResource::collection($settings)->toResponse($request);
    }

    /**
     * Get the document numbers setting of a specific smtp setting
     *
     *  @param string $companyId
     *  @param Request $request
     * @return JsonResponse
     */
    public function view(string $companyId, $id, Request $request): JsonResponse
    {
        $setting = $this->smtpSettingService->findById($id);

        return SmtpSettingsResource::make($setting)->toResponse($request);
    }

    /**
     * Create the document numbers setting of a specific smtp setting
     *
     * @param string $companyId
     * @param SmtpSettingCreateRequest $smtpSettingUpdateRequest
     * @return JsonResponse
     */
    public function create(SmtpSettingCreateRequest $smtpSettingUpdateRequest): JsonResponse
    {
        $settingDTO = new SmtpSettingDTO($smtpSettingUpdateRequest->allSafe());
        $setting = $this->smtpSettingService->create($settingDTO);

        return SmtpSettingsResource::make($setting)->toResponse($smtpSettingUpdateRequest);
    }

    /**
     * Update the document numbers setting of a specific smtp setting
     *
     * @param string $companyId
     * @param SmtpSettingUpdateRequest $smtpSettingUpdateRequest
     * @return JsonResponse
     */
    public function update(
        string $companyId,
        string $id,
        SmtpSettingUpdateRequest $smtpSettingUpdateRequest
    ): JsonResponse {
        $settingDTO = new SmtpSettingDTO($smtpSettingUpdateRequest->allSafe());
        $setting = $this->smtpSettingService->update($id, $settingDTO);

        return SmtpSettingsResource::make($setting)->toResponse($smtpSettingUpdateRequest);
    }

    /**
     * Mark as given smtp settings as default
     *
     * @param string $companyId
     * @param string $id
     * @return JsonResponse
     */
    public function markAsDefault(string $companyId, string $id, Request $response): JsonResponse
    {
        $setting = $this->smtpSettingService->markAsDefault($id);

        return SmtpSettingsResource::make($setting)->toResponse($response);
    }

    /**
     * Delte the document numbers setting of a specific smtp setting
     *
     * @param string $companyId
     * @param string $id
     * @return JsonResponse
     */
    public function delete(string $companyId, string $id): JsonResponse
    {
        $status = $this->smtpSettingService->delete($id);

        return response()->json(array('status' => $status));
    }
}
