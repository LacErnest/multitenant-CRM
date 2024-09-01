<?php


namespace App\Services;

use App\Contracts\Repositories\EmployeeRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Enums\EmployeeStatus;
use App\Enums\EmployeeType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeHistory;
use App\Models\Order;
use App\Models\Project;
use App\Models\ProjectEmployee;
use App\Repositories\EmployeeRepository;
use App\Repositories\ServiceRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class EmployeeService
{
    protected EmployeeRepository $employee_repository;

    protected EmployeeRepositoryInterface $employeeRepository;

    public function __construct(EmployeeRepository $employee_repository, EmployeeRepositoryInterface $employeeRepository)
    {
        $this->employee_repository = $employee_repository;
        $this->employeeRepository = $employeeRepository;
    }

    public function get($employee_id)
    {
        return $this->employee_repository->get($employee_id);
    }

    public function create(array $attributes): Employee
    {
        $randomEmployee = $this->employeeRepository->firstByOrNull('status', EmployeeStatus::active()->getIndex());
        if ($randomEmployee) {
            $attributes['can_be_borrowed'] = $randomEmployee->can_be_borrowed;
        }
        $employee = $this->employee_repository->create($attributes);
        $this->createSalaryService($employee);

        return $employee;
    }

    public function update(array $attributes, string $employee_id): Employee
    {
        return $this->employee_repository->update($attributes, $employee_id);
    }

    public function suggest($company_id, Request $request, $value)
    {
        return $this->employee_repository->suggest($company_id, $request, $value);
    }

    public function suggestRole(?string $value): array
    {
        $suggestions = [];
        $searchQuery = [];

        if (!($value === null)) {
            $limit = 5;
            array_push(
                $searchQuery,
                [
                'bool' => [
                        'should' => [
                            [
                                'wildcard' => [
                                    'role.keyword' => [
                                        'value' => '*' . $value . '*'
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'role.keyword' => [
                                        'value' => $value . '*',
                                        'boost' => 5
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'role.keyword' => [
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

        $query = [
          'query' => [
              'bool' => [
                  'must' => array_merge(
                      [
                          ['bool' => [
                              'must' => [
                                  ['exists' => ['field' => 'role']]
                              ]
                          ]]
                      ],
                      $searchQuery
                  )
              ]
          ]
        ];

        $roles = Employee::searchBySingleQuery($query, $limit);

        foreach ($roles['hits']['hits'] as $hit) {
            ;
            $suggestions[] = $hit['_source']['role'];
        }

        return $suggestions;
    }

    public function addEmployeeFile(string $employeeId, array $attributes)
    {
        $employee = $this->employeeRepository->firstById($employeeId);

        if (Arr::exists($attributes, 'file') && !($attributes['file'] === null)) {
            $doc = Arr::get($attributes, 'file', false);
            $mimetype = getDocumentMimeType($doc);
            $fileName = Arr::get($attributes, 'file_name', false);
            $employee->addMediaFromBase64($attributes['file'])
            ->sanitizingFileName(function ($fileName) {
                return strtolower(str_replace(['#', '/', '\\', ' ', '?', '*', '<', '>'], '-', $fileName));
            })
              ->usingFileName($fileName . '.' . $mimetype)->toMediaCollection('cv');
        }

        return $employee->load('media')->getMedia('cv');
    }

    public function fileDownload(string $employeeId, string $fileId)
    {
        $employee = $this->employeeRepository->firstById($employeeId);
        $file = $employee->getMedia('cv')->where('uuid', $fileId)->first();

        if ($file) {
            return $file;
        }
        throw new UnprocessableEntityHttpException('File not found.');
    }

    public function deleteFile(string $employeeId, string $fileId)
    {
        $employee = $this->employeeRepository->firstById($employeeId);
        $file = $employee->getMedia('cv')->where('uuid', $fileId)->first();

        if ($file) {
            $file->delete();
            return $employee->load('media')->getMedia('cv');
        } else {
            throw new UnprocessableEntityHttpException('File not found.');
        }
    }

    public function setCompanyCurrencyAsDefaultCurrency(): void
    {
        $employeeIds = $this->employeeRepository->getAll()->pluck('id')->toArray();
        $currency = Company::find(getTenantWithConnection())->currency_code;
        $this->employeeRepository->massUpdate($employeeIds, ['default_currency' => $currency]);
    }

    public function findBorrowedEmployee(string $employeeId): ?Employee
    {
        $query = [
          'query' => [
              'bool' => [
                  'must' => [
                      'match' => ['id' => $employeeId]
                  ]
              ]
          ]
        ];

        $result = Employee::searchAllTenantsQuery('employees', $query);

        if (!empty($result['hits']['hits'])) {
            $employee = App::make(Employee::class);
            $employee->id = $result['hits']['hits'][0]['_source']['id'];
            $employee->default_currency = $result['hits']['hits'][0]['_source']['default_currency'];
            $employee->salary = $result['hits']['hits'][0]['_source']['salary'];
            $employee->working_hours = $result['hits']['hits'][0]['_source']['working_hours'];
            $employee->can_be_borrowed = $result['hits']['hits'][0]['_source']['can_be_borrowed'];
            $employee->name = $result['hits']['hits'][0]['_source']['name'];
            $employee->first_name = $result['hits']['hits'][0]['_source']['first_name'];
            $employee->last_name = $result['hits']['hits'][0]['_source']['last_name'];
            $employee->type = $result['hits']['hits'][0]['_source']['type'];
            $employee->status = $result['hits']['hits'][0]['_source']['status'];
            $employee->email = $result['hits']['hits'][0]['_source']['email'];
            $employee->phone_number = $result['hits']['hits'][0]['_source']['phone_number'];
            $employee->company_id = $result['hits']['hits'][0]['_source']['company_id'];
            $employee->is_pm = $result['hits']['hits'][0]['_source']['is_pm'];
            $employee->role = $result['hits']['hits'][0]['_source']['role'];
            $employee->country = $result['hits']['hits'][0]['_source']['country'];
            $employee->addressline_1 = $result['hits']['hits'][0]['_source']['addressline_1'];
            $employee->addressline_2 = $result['hits']['hits'][0]['_source']['addressline_2'];
            $employee->city = $result['hits']['hits'][0]['_source']['city'];
            $employee->region = $result['hits']['hits'][0]['_source']['region'];
            $employee->postal_code = $result['hits']['hits'][0]['_source']['postal_code'];
            $employee->non_vat_liable = false;

            return $employee;
        }

        return null;
    }

    public function getActiveEmployees(array $attributes)
    {
        $startOfMonth = strtotime($attributes['month'] . '/01/' . $attributes['year']);
        $endOfMonth = date('Y-m-d', strtotime('+1 month', $startOfMonth));

        return EmployeeHistory::with('employee', 'employee.projects')
          ->whereHas('employee', function ($query) {
            $query->where('overhead_employee', false);
          })->whereDate('start_date', '<', $endOfMonth)
          ->where(function ($query) use ($endOfMonth) {
              $query->whereDate('end_date', '>=', $endOfMonth)
                  ->orWhere('end_date', null);
          })->get();
    }

    public function editProjectHours(string $companyId, array $attributes): string
    {
        $order = Order::findOrFail($attributes['order_id']);
        if (OrderStatus::isDraft($order->status)) {
            throw new UnprocessableEntityHttpException('Order is in draft status.');
        }

        $employee = Employee::findOrFail($attributes['employee_id']);
        if (!$employee->salary || $employee->salary == 0) {
            throw new UnprocessableEntityHttpException('Employee has no salary set.');
        }
        if (!$employee->working_hours || $employee->working_hours == 0) {
            throw new UnprocessableEntityHttpException('Employee has no working hours set.');
        }

        $project = Project::findOrFail($order->project_id);

        if (UserRole::isPm_restricted(auth()->user()->role) && $project->project_manager_id != auth()->user()->id) {
            throw new UnauthorizedException();
        }

        $month = $attributes['month'] . '-01';
        $employeeCost = safeDivide($employee->salary, $employee->working_hours) * $attributes['hours'];

        $projectEmployee = ProjectEmployee::where([['project_id', $order->project_id], ['employee_id', $employee->id], ['month', $month]])->first();
        if ($projectEmployee) {
            $extraHours = $attributes['hours'] - $projectEmployee->hours;
            $employee->projects()->wherePivot('month', $month)
            ->updateExistingPivot($order->project_id, [
                'hours' => $attributes['hours'],
                'employee_cost' => $employeeCost,
            ]);
            calculateEmployeeCosts(
                $companyId,
                $employee,
                $project,
                $extraHours,
                $projectEmployee->currency_rate_dollar,
                $projectEmployee->currency_rate_employee,
                $month
            );
        } else {
            $rates = getCurrencyRates();
            $rateEurToUsd = getOrderEurToUsdRate($companyId, $order->id);
            $employeeRate = $rates['rates'][CurrencyCode::make((int)$employee->default_currency)->__toString()];

            $employee->projects()->attach($order->project_id, [
            'hours' => $attributes['hours'],
            'employee_cost' => $employeeCost,
            'currency_rate_dollar' => $rateEurToUsd,
            'currency_rate_employee' => $employeeRate,
            'month' => $month,
            ]);
            calculateEmployeeCosts(
                $companyId,
                $employee,
                $project,
                $attributes['hours'],
                $rateEurToUsd,
                $employeeRate,
                $month
            );
        }

        return 'Employee hours set on order.';
    }

    public function deleteProjectHours(string $companyId, array $attributes): string
    {
        $order = Order::findOrFail($attributes['order_id']);
        if (OrderStatus::isDraft($order->status)) {
            throw new UnprocessableEntityHttpException('Order is in draft status.');
        }

        $project = Project::findOrFail($order->project_id);
        if (UserRole::isPm_restricted(auth()->user()->role) && $project->project_manager_id != auth()->user()->id) {
            throw new UnauthorizedException();
        }

        $employee = Employee::findOrFail($attributes['employee_id']);
        $month = $attributes['month'] . '-01';
        $projectEmployee = ProjectEmployee::where([['project_id', $order->project_id], ['employee_id', $employee->id], ['month', $month]])->first();

        if ($projectEmployee) {
            $employee->projects()->wherePivot('month', $month)->detach($order->project_id);
            calculateEmployeeCosts(
                $companyId,
                $employee,
                $project,
                0 - $projectEmployee->hours,
                $projectEmployee->currency_rate_dollar,
                $projectEmployee->currency_rate_employee,
                $month
            );
        }

        return 'Employee hours removed from order.';
    }

    private function createSalaryService(Employee $employee)
    {
        if (EmployeeType::isContractor($employee->type)) {
            $serviceRepository = App::make(ServiceRepository::class);
            $attributes = [
            'resource_id' => $employee->id,
              'name'        => 'Salary',
              'price'       => $employee->salary,
              'price_unit'  => 'Fixed Price',
            ];
            $serviceRepository->create($attributes);
        }
    }
}
