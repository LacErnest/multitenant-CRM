<?php

namespace App\Http\Controllers\Employee;

use App\Enums\TablePreferenceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeHistory\CreateEmployeeHistoryRequest;
use App\Http\Requests\EmployeeHistory\EmployeeHistoryDeleteRequest;
use App\Http\Requests\EmployeeHistory\EmployeeHistoryGetRequest;
use App\Http\Requests\EmployeeHistory\UpdateEmployeeHistoryRequest;
use App\Models\EmployeeHistory;
use App\Services\EmployeeHistoryService;
use Illuminate\Http\Request;

class EmployeeHistoryController extends Controller
{
    protected string $model = EmployeeHistory::class;

    /**
     * @var EmployeeHistoryService $employeeHistoryService
     */
    protected EmployeeHistoryService $employeeHistoryService;

    public function __construct(EmployeeHistoryService $employeeHistoryService)
    {
        $this->employeeHistoryService = $employeeHistoryService;
    }

    public function getEmployeeHistories(string $companyId, string $employeeId, EmployeeHistoryGetRequest $employeeHistoryGetRequest): array
    {
        $employeeHistoryGetRequest['employee'] = $employeeId;

        return parent::getAll(
            $companyId,
            $employeeHistoryGetRequest,
            TablePreferenceType::employee_histories()->getIndex()
        );
    }

    public function createEmployeeHistory(string $companyId, string $employeeId, CreateEmployeeHistoryRequest $createEmployeeHistoryRequest)
    {
        $this->employeeHistoryService->create($companyId, $employeeId, $createEmployeeHistoryRequest->allSafe());
    }

    public function updateEmployeeHistory(string $companyId, string $employeeId, string $historyId, UpdateEmployeeHistoryRequest $createEmployeeHistoryRequest)
    {
        $this->employeeHistoryService->update($companyId, $employeeId, $historyId, $createEmployeeHistoryRequest->allSafe());
    }

    public function deleteEmployeeHistory(string $companyId, string $employeeId, string $historyId, EmployeeHistoryDeleteRequest $employeeHistoryDeleteRequest)
    {
        $this->employeeHistoryService->delete($historyId);
    }
}
