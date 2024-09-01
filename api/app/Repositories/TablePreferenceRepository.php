<?php


namespace App\Repositories;


use App\Enums\TablePreferenceType;
use App\Models\TablePreference;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use function GuzzleHttp\Promise\all;

class TablePreferenceRepository
{
    protected $table_preference;

    public function __construct(TablePreference $table_preference)
    {
        $this->table_preference = $table_preference;
    }

    public function getPreferences($key, $entity)
    {
        $user = auth()->user();
        $table = strtolower(TablePreferenceType::make((int)$entity)->getName());
        $allColumns = $this->getAllColumns($table);
        $defaulColumns = config("table-config.$table.default");
        $defaultFilters = config("table-config.$table.default_filter");
        $tablePreference = $user->table_preferences()->firstOrCreate(['type' => $entity, 'key' => $key], ['columns' => json_encode($defaulColumns), 'filters' => json_encode($defaultFilters), 'sorts' => json_encode(config("table-config.$table.default_sorting"))]);
        $userColumns = json_decode($tablePreference->columns);
        $sorts = json_decode($tablePreference->sorts);
        $filters = json_decode($tablePreference->filters, true);
        if ($filters) {
            $filters = array_map(function ($item) use ($allColumns) {
                if ($item['type'] == 'uuid') {
                    $model_name = $this->getColumnTypes([$item['prop']], $allColumns);
                    $model_name = Str::camel($model_name[0]['model']);
                    $model_name = ucfirst($model_name);
                    if ($model_name == 'User' || $model_name == 'Customer' || $model_name == 'Contact' || $model_name == 'Employee' || $model_name == 'Resource' || $model_name == 'LegalEntity') {
                        $model = "App\Models\ $model_name";
                        $model = str_replace(' ', '', $model);
                        if (is_array($item['value']) && empty($item['value']['id'])) {
                            foreach ($item['value'] as $index => $id) {
                                $result = $model::find($id);
                                if ($result) {
                                        $item['value'][$index] = [
                                      'name'=>$result->name,
                                      'id'=>$id,
                                        ];
                                }
                            }
                        } else {
                            $id = $item['value'][0] ?? $item['value']['id'];
                            $result = $model::find($id);
                            if ($result) {
                                $item['value'] = [
                                [
                                'name'=>$result->name,
                                'id'=>$id,
                                ]
                                ];
                            }
                        }
                    }
                }
                return $item;
            }, $filters);
        }
        $userColumns = $this->getColumnTypes($userColumns, $allColumns);

        return response()->json(['columns' => $userColumns, 'sorts' => $sorts, 'filters' => $filters, 'all_columns' => $allColumns,'default_columns'=>$defaulColumns, 'default_filters'=>$defaultFilters]);
    }

    public function updatePreferences($attributes, $key)
    {
        if (!empty($attributes['filters'])) {
            $attributes['filters'] = array_map(function ($filter) {
                if ($filter['type'] == 'string') {
                    $filter['value'] = array_map(function ($value) {
                        $query_array = explode(' ', str_replace(['"', '+', '=', '-', '&&', '||', '>', '<', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '\\', '/'], '?', trim($value)));
                        $query_array = array_filter($query_array);
                        $query_string = implode(' ', $query_array);
                        $value = strtolower($query_string);
                        return $value;
                    }, $filter['value']);
                }
                return $filter;
            }, $attributes['filters']);
        }

        if (!empty($attributes['custom_key']) && $key == 'analytics') {
            switch ($attributes['custom_key']) {
                case 'analytics_invoices_overdue':
                    $endDate = array_map(function ($r) {
                        if ($r['prop'] == 'due_date') {
                              return $r['value'][1];
                        }
                    }, $attributes['filters']);
                      $endDate = Arr::flatten(array_filter($endDate));
                      $customConfig = config('table-config.invoices.custom_filters.overdue');
                      $customConfig[0]['value'][1] = $endDate[0];
                      $attributes['filters'] = array_merge($attributes['filters'], $customConfig);
                    break;
                case 'intra_company':
                    $attributes['filters'][] =
                    [
                    'prop' => 'intra_company',
                    'type' => 'boolean',
                    'value' => [
                    true
                    ]
                    ]
                    ;
                    break;
            }
        }

        $user = auth()->user();
        if (!empty($attributes['columns'])) {
            $columns = Arr::pluck(array_filter($attributes['columns']), 'prop');
        } else {
            $currentTablePreferences = TablePreference::where(['type' => $attributes['entity'], 'key' => $key, 'user_id' => $user->id])->first();
            $table = strtolower(TablePreferenceType::make((int)$attributes['entity'])->getName());
            $columns = $currentTablePreferences ? json_decode($currentTablePreferences->columns) : config("table-config.$table.default");
        }

        return $user->table_preferences()->updateOrCreate(
            [
              'key' => $key,
              'type' => $attributes['entity']
            ],
            [
              'columns' => json_encode($columns),
              'sorts' => !empty($attributes['sorts']) ? json_encode($attributes['sorts']) : null,
              'filters' => !empty($attributes['filters']) ? json_encode($attributes['filters']) : null
            ]
        );
    }

    private function getAllColumns($table)
    {
        return config("table-config.$table.all");
    }

    private function getColumnTypes($columns, $allColumns)
    {
        $columns = array_map(function ($item) use ($allColumns) {
            $key = array_search($item, array_column($allColumns, 'prop'));
            $r = [];
            if ($key !== null || $key !== false) {
                $r['prop'] = $allColumns[$key]['prop'];
                $r['name'] = $allColumns[$key]['name'];
                $r['type'] = $allColumns[$key]['type'];
                if (!empty($allColumns[$key]['format'])) {
                    $r['format'] = $allColumns[$key]['format'];
                }
                if ($allColumns[$key]['type'] == 'enum') {
                    $r['enum'] = $allColumns[$key]['enum'];
                }
                if ($allColumns[$key]['type'] == 'uuid') {
                    $r['model'] = $allColumns[$key]['model'];
                }
                if (array_key_exists('cast', $allColumns[$key])) {
                    $r['cast'] = $allColumns[$key]['cast'];
                }
                if (array_key_exists('check_on', $allColumns[$key])) {
                    $r['check_on'] = $allColumns[$key]['check_on'];
                }
            }
            return $r;
        }, $columns);
        return $columns;
    }
}

// todo changes
