<?php


namespace App\Services;

use App\Http\Requests\Settings\SettingUpdateRequest;
use App\Repositories\SettingRepository;

class SettingService
{
    protected SettingRepository $setting_repository;

    public function __construct(SettingRepository $setting_repository)
    {
        $this->setting_repository = $setting_repository;
    }

    public function getSetting()
    {
        return $this->setting_repository->get();
    }

    public function updateSettings(SettingUpdateRequest $request)
    {
        return $this->setting_repository->update($request->allSafe());
    }
}
