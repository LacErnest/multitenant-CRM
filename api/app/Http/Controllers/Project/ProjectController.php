<?php

namespace App\Http\Controllers\Project;

use App\Enums\CurrencyCode;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\AssignEmployeeRequest;
use App\Http\Requests\Project\DeleteEmployeeRequest;
use App\Http\Resources\Project\EmployeeOfProjectResource;
use App\Jobs\ElasticUpdateAssignment;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectEmployee;
use App\Services\EmployeeService;
use App\Services\ProjectEmployeeService;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ProjectController extends Controller
{
    protected string $model = Project::class;

    private ProjectEmployeeService $projectEmployeeService;
    /**
     * @param ProjectEmployeeService $projectEmployeeService
     */
    public function __construct(ProjectEmployeeService $projectEmployeeService)
    {
        $this->projectEmployeeService = $projectEmployeeService;
    }

    /**
     * Get one project of a specific company
     * @param $company_id
     * @param $project_id
     * @return JsonResponse
     */
    public function getSingle($company_id, $project_id)
    {
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($project_id);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }
        return parent::getSingle($company_id, $project_id);
    }

    /**
     * Get an employee of a project
     * @param $company_id
     * @param $project_id
     * @param $employee_id
     * @return EmployeeOfProjectResource
     */
    public function getEmployee($company_id, $project_id, $employee_id)
    {
        if (UserRole::isSales(auth()->user()->role)) {
            throw new UnauthorizedException();
        }
        $project = Project::findOrFail($project_id);

        if (UserRole::isPm_restricted(auth()->user()->role)) {
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        $projectResource = $project->employees()->where('employee_id', $employee_id)->first();
        if ($projectResource) {
            return EmployeeOfProjectResource::make($projectResource);
        }
        throw new ModelNotFoundException();
    }

    /**
     * Assign an employee to a project
     * @param $company_id
     * @param $project_id
     * @param $employee_id
     * @param AssignEmployeeRequest $request
     * @return JsonResponse
     */
    public function assignEmployee($company_id, $project_id, $employee_id, AssignEmployeeRequest $request)
    {
        $project = Project::findOrFail($project_id);

        if (UserRole::isPm_restricted(auth()->user()->role)) {
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        if ($this->projectEmployeeService->create($company_id, $project_id, $employee_id, $request)) {
            return response()->json(['message' => 'Employee added to project']);
        }

        throw new ModelNotFoundException();
    }

    /**
     * Update a specific employee of a project
     * @param $company_id
     * @param $project_id
     * @param $employee_id
     * @param AssignEmployeeRequest $request
     * @return JsonResponse
     */
    public function updateEmployee($company_id, $project_id, $employee_id, AssignEmployeeRequest $request)
    {
        $project = Project::findOrFail($project_id);

        if (UserRole::isPm_restricted(auth()->user()->role)) {
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        if ($this->projectEmployeeService->update($company_id, $project_id, $employee_id, $request)) {
            return response()->json(['message' => 'The project employee has been updated.']);
        }

        throw new ModelNotFoundException();
    }

    /**
     * Delete all employees from a project
     * @param $company_id
     * @param $project_id
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteEmployee($company_id, $project_id, DeleteEmployeeRequest $request)
    {
        $project = Project::findOrFail($project_id);

        if ($project) {
            if (UserRole::isPm_restricted(auth()->user()->role)) {
                if ($project->project_manager_id != auth()->user()->id) {
                    throw new UnauthorizedException();
                }
            }

            $items = $request->all();
            foreach ($items as $item) {
                $employee = $project->employees()->where('employee_id', $item)->first();
                if (!$employee) {
                    $employeeService = App::make(EmployeeService::class);
                    $employee = $employeeService->findBorrowedEmployee($item);
                }
                if ($employee) {
                    $projectEmployee = ProjectEmployee::where([['project_id', $project_id], ['employee_id', $employee->id]])->get();
                    if ($projectEmployee->isNotEmpty()) {
                        $employee->hours = $projectEmployee->sum('hours');
                        $project->employees()->detach($item);
                        calculateEmployeeCosts(
                            $company_id,
                            $employee,
                            $project,
                            0 - $employee->hours,
                            $projectEmployee->first()->currency_rate_dollar,
                            $projectEmployee->first()->currency_rate_employee,
                            $employee->month
                        );
                    }
                }
            }

            return response()->json(['message' => count($request->all()) . ' employee(s) removed from project']);
        }
        throw new ModelNotFoundException();
    }
}
