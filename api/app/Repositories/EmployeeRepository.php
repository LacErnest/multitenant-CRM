<?php


namespace App\Repositories;

use App\Enums\CurrencyCode;
use App\Enums\EmployeeStatus;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Employee as EmployeeModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * Class EmployeeRepository
 *
 * @deprecated
 */
class EmployeeRepository
{
    protected EmployeeModel $employee;
    protected Address $address;

    public function __construct(EmployeeModel $employee, Address $address)
    {
        $this->employee = $employee;
        $this->address = $address;
    }

    public function get($employee_id)
    {
        if (!$resource = $this->employee->find($employee_id)) {
            throw new ModelNotFoundException();
        }

        return $resource;
    }

    public function create(array $attributes): Employee
    {
        if ($employee = $this->employee->create($attributes)) {
            $address = $this->address->create($attributes);
            $address->employees()->save($employee);
        }

        return $this->employee->find($employee->id);
    }

    public function update(array $attributes, string $employee_id): Employee
    {
        if (!$employee = $this->employee->find($employee_id)) {
            throw new ModelNotFoundException();
        }

        $employee->update($attributes);
        $employee->address->update($attributes);

        return $employee;
    }

    public function suggest($company_id, Request $request, $value)
    {
        return $this->autoGet($company_id, $request, $value);
    }

    private function autoGet($company_id, Request $request, $value = null)
    {
        $searchQuery = [];
        $typeQuery = [];
        $statusQuery = [];
        $status = [];
        $borrowedQuery = [];
        $isPmQuery = [];

        $type = !($request->input('type') === null) ? (int)$request->input('type') : null;
        $isPm = !($request->input('is_pm') === null) ? (bool)$request->input('is_pm') : null;

        if (!($request->input('status') === null)) {
            $input = str_replace('[', '', $request->input('status'));
            $input = str_replace(']', '', $input);
            $items = explode(',', $input);
            foreach ($items as $item) {
                array_push($status, $item);
            }
        }

        if (!($value === null)) {
            $limit = 5;
            $query_array = explode(' ', str_replace(['"', '+', '=', '-', '&&', '||', '>', '<', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '\\', '/'], '?', trim($value)));
            $query_array = array_filter($query_array);
            $query_string = implode(' ', $query_array);
            $value = strtolower($query_string);

            array_push(
                $searchQuery,
                [
                    'bool' => [
                        'should' => [
                            [
                                'wildcard' => [
                                    'first_name' => [
                                        'value' => '*' . $value . '*'
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'last_name' => [
                                        'value' => '*' . $value . '*'
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'name.keyword' => [
                                        'value' => '*' . $value . '*'
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'first_name' => [
                                        'value' => $value . '*',
                                        'boost' => 5
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'last_name' => [
                                        'value' => $value . '*',
                                        'boost' => 5
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'name.keyword' => [
                                        'value' => $value . '*',
                                        'boost' => 5
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'first_name' => [
                                        'value' => $value,
                                        'boost' => 10
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'last_name' => [
                                        'value' => $value,
                                        'boost' => 10
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'name.keyword' => [
                                        'value' => $value,
                                        'boost' => 10
                                    ]
                                ]
                            ],
                        ]
                    ]
                ]
            );
        } else {
            $limit = null;
        }

        if (!($type === null)) {
            array_push($typeQuery, ['bool' => ['should' => [['match' => ['type' => $type]]]]]);
        }

        if (!($isPm === null)) {
            array_push($isPmQuery, [
            ['bool' => [
              'should' => [
                  ['match' => ['is_pm' => $isPm]]],
              ]
            ]
            ]);
        }

        if (!empty($status)) {
            array_push($statusQuery, ['bool' => ['should' => [['terms' => ['status' => $status]]]]]);
        }

        array_push($borrowedQuery, [
          'bool' => [
              'should' => [
                  ['match' => ['company_id' => $company_id]],
                  ['match' => ['can_be_borrowed' => true]],
              ]
          ]
        ]);

        $query = [
          'query' => [
              'bool' => [
                  'must' => array_merge(
                      [
                          ['bool' => ['must' => ['match' => ['overhead_employee' => false]]]]
                      ],
                      $searchQuery,
                      $typeQuery,
                      $statusQuery,
                      $borrowedQuery,
                      $isPmQuery
                  )]
          ]
        ];

        $users = Employee::searchAllTenantsQuery('employees', $query, $limit);

        $result = $users['hits']['hits'];

        $company = Company::find($company_id);
        $rates = getCurrencyRates();

        if ($company->currency_code == CurrencyCode::EUR()->getIndex() || UserRole::isAdmin(auth()->user()->role)) {
            $rate = 1;
            $currencySign = '€';
        } else {
            $rate = $rates['rates']['USD'];
            $currencySign = '$';
        }

        $result = array_map(function ($r) use ($rates, $rate, $currencySign) {
            $employeeCurrency = CurrencyCode::make((int)$r['_source']['default_currency'])->__toString();
            $employeeRate = $rates['rates'][$employeeCurrency];
            if (empty($r['_source']['working_hours'])) {
                $hourlyRate = 0;
            } else {
                $hourlyRate = round(($r['_source']['salary'] / ($r['_source']['working_hours'])) * (1/$employeeRate) * $rate, 2);
            }

            $r =  Arr::only($r['_source'], ['id', 'name', 'status']);
            $r['name'] = $r['name'] . ' (hourly rate: ' . $currencySign . $hourlyRate .')';

            return $r;
        }, $result);
        return response()->json($result);
    }
}
