<?php

namespace App\Http\Requests\Preferences;

use App\Enums\TablePreferenceType;
use App\Http\Requests\BaseRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Validator;

class TablePreferenceRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'entity' => 'required|integer|enum:' . TablePreferenceType::class,
          'columns' => 'array',
          'columns.*.prop' => 'string|max:50',
          'columns.*.check_on' => 'boolean|nullable',
          'sorts' => 'array',
          'sorts.*' => 'array',
          'sorts.*.prop' => 'string|max:50|required_with:sorts.*.dir',
          'sorts.*.dir' => 'string|max:20|required_with:sorts.*.prop',
          'filters' => 'array',
          'filters.*' => 'array',
          'filters.*.prop' => 'string|max:50|required_with:filters.*.type,filters.*.value',
          'filters.*.type' => 'string|max:20|required_with:filters.*.prop,filters.*.value',
          'filters.*.value' => 'array',
          'custom_key' => 'string|nullable|max:50',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (empty($this->key) || $this->key != 'analytics') {
                if (empty($this->columns)) {
                    $validator->errors()->add('columns', 'Columns is required.');
                    return;
                }
            }
            $table = strtolower(TablePreferenceType::make((int)$this->entity)->getName());
            $columns = config("table-config.$table.all");
            $columns = Arr::pluck($columns, 'prop');
            if (!empty($this->columns)) {
                $filtered_columns = array_filter($this->columns);
                $new_columns = array_map(function ($item) use ($validator, $columns) {
                    $item = $item['prop'];
                    if (!in_array($item, $columns)) {
                        $validator->errors()->add('columns', $item . ' is not a column from the selected database table');
                    }
                }, $filtered_columns);
            }
            if (!empty($this->sorts)) {
                foreach ($this->sorts as $sort) {
                    if (!empty($sort['prop'])) {
                        if (!in_array($sort['prop'], $columns)) {
                            $validator->errors()->add('sorts', $sort['prop'] . ' is not a column from the selected database table');
                        }
                    }

                    if (!empty($sort['dir'])) {
                        if ($sort['dir'] != 'asc' && $sort['dir'] != 'desc') {
                            $validator->errors()->add('sorts', $sort['dir'] . ' is not a correct sorting direction');
                        }
                    }
                }
            }
            if (!empty($this->filters)) {
                foreach ($this->filters as $filter) {
                    if (empty($filter['value']) && $filter['type'] != 'custom') {
                        $validator->errors()->add('filters', 'A value is required when using filters');

                        continue;
                    }

                    if ($filter['type'] != 'custom' && !is_array(Arr::get($filter, 'value', false))) {
                        $validator->errors()->add('filters', 'The value for this kind of filter must be placed in an array.');

                        continue;
                    }

                    if (!empty($filter['prop'])) {
                        if (!in_array($filter['prop'], $columns)) {
                            $validator->errors()->add('filters', $filter['prop'] . ' is not a column from the selected database table');
                        }
                    }

                    if (!empty($filter['type'])) {
                        if ($filter['type'] == 'string') {
                            foreach ($filter['value'] as $value) {
                                if (gettype($value) != 'string') {
                                    $validator->errors()->add('filters', 'The value for this kind of filter must be a string');
                                }
                            }
                        }

                        if ($filter['type'] == 'enum') {
                            foreach ($filter['value'] as $value) {
                                if (gettype($value) != 'integer') {
                                    $validator->errors()->add('filters', 'The value for this kind of filter must be an integer');
                                }
                            }
                        }

                        if ($filter['type'] == 'integer') {
                            foreach ($filter['value'] as $value) {
                                if (array_key_exists('from', $value) && !empty($value['from'])) {
                                    if (gettype($value['from']) != 'integer') {
                                        $validator->errors()->add('filters', 'The value for this kind of filter must be integer');
                                    }
                                }
                                if (array_key_exists('to', $value) && !empty($value['to'])) {
                                    if (gettype($value['to']) != 'integer') {
                                        $validator->errors()->add('filters', 'The value for this kind of filter must be integer');
                                    }
                                }
                            }
                        }

                        if ($filter['type'] == 'decimal') {
                            foreach ($filter['value'] as $value) {
                                if (array_key_exists('min', $value) && !empty($value['min'])) {
                                    if (gettype($value['min']) != 'integer' && gettype($value['min']) != 'double') {
                                        $validator->errors()->add('filters', 'The value for this kind of filter must be numeric');
                                    }
                                }
                                if (array_key_exists('max', $value) && !empty($value['max'])) {
                                    if (gettype($value['max']) != 'integer' && gettype($value['max']) != 'double') {
                                        $validator->errors()->add('filters', 'The value for this kind of filter must be numeric');
                                    }
                                }
                            }
                        }

                        if ($filter['type'] == 'date') {
                            if (array_key_exists('0', $filter['value']) && !empty($filter['value'][0])) {
                                if (!strtotime($filter['value'][0])) {
                                    $validator->errors()->add('filters', 'The value for this kind of filter must be a date');
                                }
                            }

                            if (array_key_exists('1', $filter['value']) && !empty($filter['value'][1])) {
                                if (!strtotime($filter['value'][1])) {
                                    $validator->errors()->add('filters', 'The value for this kind of filter must be a date');
                                }
                            }
                        }

                        if ($filter['type'] == 'uuid' && empty($filter['cast'])) {
                            $filter['value'] = filterUuidFormat($filter['value']);
                            foreach ($filter['value'] as $value) {
                                if (gettype($value) != 'string' || !($this->isValidateUUID32Bytes($value) || $this->isValidateUUID16Bytes($value))) {
                                    $validator->errors()->add('filters', $value.' The value for this kind of filter must be a uuid');
                                }
                            }
                        }
                    }
                }
            }
        });

        return;
    }

    private function isValidateUUID32Bytes($uuid)
    {
        $pattern = '/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{12}$/i';
        return filter_var($uuid, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $pattern)));
    }

    private function isValidateUUID16Bytes($uuid)
    {
        $pattern = '/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i';
        return filter_var($uuid, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $pattern)));
    }
}
