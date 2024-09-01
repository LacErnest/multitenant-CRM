<?php

namespace App\Http\Controllers\Preferences;

use App\Http\Controllers\Controller;
use App\Http\Requests\Preferences\TablePreferenceRequest;
use App\Services\TablePreferenceService;

class TablePreferenceController extends Controller
{
    protected $table_preference_service;

    public function __construct(TablePreferenceService $table_preference_service)
    {
        $this->table_preference_service = $table_preference_service;
    }

    /**
     * Get the table preferences of a specific datatable for the logged in user
     *
     * @param $company_id
     * @param $key
     * @param $entity
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTablePreferences($company_id, $key, $entity)
    {
        if (ctype_alpha($key)) {
            return $this->table_preference_service->getPreferences($key, $entity);
        }
        return response()->json(['message' => 'Key must only contain alphabetical letters.'], 422);
    }

    /**
     * Update the table preferences of a specific datatable for the logged in user
     *
     * @param TablePreferenceRequest $request
     * @param $company_id
     * @param $key
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTablePreferences($company_id, $key, TablePreferenceRequest $request)
    {
        if (ctype_alpha($key)) {
            try {
                $test = $this->table_preference_service->testPreferencesBeforeUpdate($request->validated());
            } catch (\Exception $e) {
                logger($e->getMessage());
                return response()->json(['message' => 'The request was not valid'], 422);
            }

            if ($test) {
                try {
                    $status =  $this->table_preference_service->updatePreferences($request, $key);
                    if ($status) {
                        return $this->table_preference_service->getPreferences($key, $request->input('entity'));
                    }
                    return response()->json(['message' => 'The request was not valid'], 422);
                } catch (\Exception $e) {
                    logger($e->getMessage());
                    return response()->json(['message' => 'The request was not valid'], 422);
                }
            }
        }
        return response()->json(['message' => 'Key must only contain alphabetical letters.'], 422);
    }

    /**
     * Get all predefined price modifiers
     */
    public function getPriceModifiers($company_id)
    {
        return config('settings.price_modifiers');
    }
}
