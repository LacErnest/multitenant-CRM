<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SettingUpdateRequest;
use App\Http\Requests\Settings\SettingGetRequest;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    protected $setting_service;

    public function __construct(SettingService $setting_service)
    {
        $this->setting_service = $setting_service;
    }
    /**
     * Get the table setting of a specific datatable for the logged in user
     *  @param $company_id
     *  @param SettingGetRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSetting(SettingGetRequest $request, $company_id): JsonResponse
    {
        return $this->setting_service->getSetting();
    }

    /**
     * Update the table setting of a specific datatable for the logged in user
     * @param $company_id
     * @param SettingUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSetting(SettingUpdateRequest $request): JsonResponse
    {
        return $this->setting_service->updateSettings($request);
    }
}
