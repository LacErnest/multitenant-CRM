<?php


namespace App\Services;


use App\Enums\TablePreferenceType;
use App\Http\Requests\Preferences\TablePreferenceRequest;
use App\Models\TablePreference;
use App\Repositories\TablePreferenceRepository;
use Illuminate\Http\JsonResponse;

class TablePreferenceService
{
    protected TablePreferenceRepository $table_preference_repository;

    public function __construct(TablePreferenceRepository $table_preference_repository)
    {
        $this->table_preference_repository = $table_preference_repository;
    }

    public function getPreferences($key, $entity)
    {
        return $this->table_preference_repository->getPreferences($key, $entity);
    }

    public function updatePreferences(TablePreferenceRequest $request, $key)
    {
        return $this->table_preference_repository->updatePreferences($request->allSafe(), $key);
    }

    public function testPreferencesBeforeUpdate(array $preferences): JsonResponse
    {
        $table = strtolower(TablePreferenceType::make((int)$preferences['entity'])->getName());
        $allColumns = config("table-config.$table.all");
        $userColumns = $preferences['columns'] ?? [];
        $sorts = $preferences['sorts'] ?? [];
        $filters = $preferences['filters'] ?? [];

        if ($filters) {
            $filters = array_map(function ($item) use ($allColumns) {
                if ($item['type'] == 'uuid') {
                    $model_name = $this->getColumnTypes([$item['prop']], $allColumns);
                    $model_name = ucfirst($model_name[0]['model']);
                    if ($model_name == 'User' || $model_name == 'Customer' || $model_name == 'Contact' || $model_name == 'Employee' || $model_name == 'Resource') {
                        $model = "App\Models\ $model_name";
                        $model = str_replace(' ', '', $model);
                        $id = $item['value'][0] ?? $item['value']['id'];
                        $result = $model::find($id);
                        if ($result) {
                            $item['value']['name'] = $result->name;
                            $item['value']['id'] = $id;
                            unset($item['value'][0]);
                        }
                    }
                }
                return $item;
            }, $filters);
        }
        $userColumns = $this->getColumnTypes($userColumns, $allColumns);
        return response()->json(['columns' => $userColumns, 'sorts' => $sorts, 'filters' => $filters, 'all_columns' => $allColumns]);
    }

    private function getColumnTypes(array $columns, array $allColumns): array
    {
        $columns = array_map(function ($item) use ($allColumns) {
            $key = array_search($item, array_column($allColumns, 'prop'));
            $r = [];
            if ($key !== null || $key !== false) {
                $r['prop'] = $allColumns[$key]['prop'];
                $r['name'] = $allColumns[$key]['name'];
                $r['type'] = $allColumns[$key]['type'];
                if ($allColumns[$key]['type'] == 'enum') {
                    $r['enum'] = $allColumns[$key]['enum'];
                }
                if ($allColumns[$key]['type'] == 'uuid') {
                    $r['model'] = $allColumns[$key]['model'];
                }
                if (array_key_exists('cast', $allColumns[$key])) {
                    $r['cast'] = $allColumns[$key]['cast'];
                }
            }
            return $r;
        }, $columns);
        return $columns;
    }

    /**
     * $tablePreferenceType refers to the TablePreferenceType enum
     * It is set as default to 12 (project_purchase_orders) because it was already used in a previous migration
     * that had no parameters at the time
     */
    public function addDetailsToTablePreferences(int $tablePreferenceType = 12): void
    {
        TablePreference::where('type', $tablePreferenceType)
          ->update(['columns' => '["number", "resource_id", "date", "delivery_date", "status", "details"]']);
    }

    public function removeDetailsFromTablePreferences(int $tablePreferenceType = 12): void
    {
        TablePreference::where('type', $tablePreferenceType)
          ->update(['columns' => '["number", "resource_id", "date", "delivery_date", "status"]']);
    }

    public function addIsBorrowedToTablePreferences(int $tablePreferenceType): void
    {
        TablePreference::where('type', $tablePreferenceType)
          ->update(['columns' => '["first_name", "last_name", "type", "status", "email", "phone_number", "hours", "is_borrowed"]']);
    }

    public function removeIsBorrowedFromTablePreferences(int $tablePreferenceType): void
    {
        TablePreference::where('type', $tablePreferenceType)
          ->update(['columns' => '["first_name", "last_name", "type", "status", "email", "phone_number", "hours"]']);
    }

    /**
     * Drop table preferences for specific entity type
     * This action will reset the table preferences
     * @param int $tablePreferenceType
     */
    public function deleteToTablePreferences(int $tablePreferenceType = 12): void
    {
        TablePreference::where('type', $tablePreferenceType)->delete();
    }
}
