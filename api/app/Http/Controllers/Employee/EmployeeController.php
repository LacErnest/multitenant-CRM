<?php

namespace App\Http\Controllers\Employee;

use App\Enums\CurrencyCode;
use App\Enums\TablePreferenceType;
use App\Enums\UserRole;
use App\Exports\DataTablesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\DownloadEmployeeRequest;
use App\Http\Requests\Employee\EmployeeDeleteProjectHoursRequest;
use App\Http\Requests\Employee\EmployeeEditProjectHoursRequest;
use App\Http\Requests\Employee\EmployeeGetMonthRequest;
use App\Http\Requests\Employee\EmployeeUploadFileRequest;
use App\Http\Requests\Export\ExportEmployeeGetRequest;
use App\Http\Requests\Import\FinalizeImportFileRequest;
use App\Http\Requests\Import\ImportFileCreateRequest;
use App\Http\Requests\Employee\EmployeeCreateRequest;
use App\Http\Requests\Employee\EmployeeGetRequest;
use App\Http\Requests\Employee\EmployeeUpdateRequest;
use App\Http\Resources\Employee\EmployeeAssignResource;
use App\Http\Resources\Employee\EmployeeFilesResource;
use App\Http\Resources\Employee\EmployeeResource;
use App\Models\Company;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Services\Export\EmployeeExporter;
use App\Services\Imports\EmployeeImportService;
use App\Services\TablePreferenceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\UnauthorizedException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class EmployeeController extends Controller
{
    protected string $model = Employee::class;

    /**
     * @var EmployeeService $employeeService
     */
    protected EmployeeService $employeeService;

    /**
     * @var EmployeeImportService $employeeImportService
     */
    protected EmployeeImportService $employeeImportService;


/**
     * EmployeeController constructor.
     *
     * @param EmployeeService       $employeeService
     * @param EmployeeImportService $employeeImportService
     */    public function __construct(
    EmployeeService $employeeService,
    EmployeeImportService $employeeImportService
) {
        $this->employeeService = $employeeService;
        $this->employeeImportService = $employeeImportService;
}

    /**
     * Get an employee of a specific company
     *
     * @param  string $companyId
     * @param  string $employeeId
     * @param  EmployeeGetRequest $employeeGetRequest
     *
     * @return EmployeeResource
     */
public function index(
    string $companyId,
    string $employeeId,
    EmployeeGetRequest $employeeGetRequest
): EmployeeResource {
    $resource = $this->employeeService->get($employeeId);

    return EmployeeResource::make($resource);
}

    /**
     * Get an employee list of a specific company
     *
     * @param  string $companyId
     * @param  EmployeeGetRequest $employeeGetRequest
     * @param  null $entity
     *
     * @return array
     */
public function all(
    string $companyId,
    EmployeeGetRequest $employeeGetRequest,
    $entity = null
): array {
    return parent::getAll(
        $companyId,
        $employeeGetRequest,
        TablePreferenceType::employees()->getIndex()
    );
}


    /**
     * Create a new employee for a specific company
     *
     * @param  EmployeeCreateRequest $employeeCreateRequest
     *
     * @return EmployeeResource
     */
public function create(EmployeeCreateRequest $employeeCreateRequest): EmployeeResource
{
    $resource = $this->employeeService->create($employeeCreateRequest->validated());

    return EmployeeResource::make($resource);
}

    /**
     * Update an employee of a specific company
     * @param  EmployeeUpdateRequest $employeeUpdateRequest
     * @param  string $companyId
     * @param  string $employeeId
     *
     * @return EmployeeResource
     */
public function update(
    string $companyId,
    string $employeeId,
    EmployeeUpdateRequest $employeeUpdateRequest
): EmployeeResource {
    $resource = $this->employeeService->update($employeeUpdateRequest->validated(), $employeeId);

    return EmployeeResource::make($resource);
}

    /**
     * Get certain users of a specific company using autocomplete
     *
     * @param  string $companyId
     * @param  Request $request
     * @param  $value
     *
     * @return JsonResponse
     */
public function suggest(string $companyId, Request $request, $value = null): JsonResponse
{
    if (UserRole::isSales(auth()->user()->role)) {
        throw new UnauthorizedException();
    }

    return $this->employeeService->suggest($companyId, $request, $value);
}

    /**
     * Upload import file for employee of a specific company
     * @param  string $companyId
     * @param  ImportFileCreateRequest $importFileCreateRequest
     *
     * @return JsonResponse
     */
public function uploadImportFile(
    string $companyId,
    ImportFileCreateRequest $importFileCreateRequest
) {
    return $this->employeeImportService->saveFile($importFileCreateRequest);
}

    /**
     * Import  data from file to resource table of a specific company
     *
     * @param string $companyId
     * @param FinalizeImportFileRequest $finalizeImportFileRequest
     *
     */
public function finalizeImportFile(
    string $companyId,
    FinalizeImportFileRequest $finalizeImportFileRequest
) {
    $import = $this->employeeImportService->import($finalizeImportFileRequest);

    if (count($import->importData) == 0) {
        throw new BadRequestHttpException();
    }

    $response = parent::getAll(
        $companyId,
        $finalizeImportFileRequest,
        TablePreferenceType::employees()->getIndex()
    );
    if ($import->haveImportError) {
        $response['notValidFileRows'] = $import->notValidFileRows;
    }

    return $response;
}

    /**
     * Export data from resource
     * @param ExportEmployeeGetRequest $request
     * @param $companyId
     * @param $employeeId
     * @param $type
     * @param $format
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */

public function export($companyId, $employeeId, $type, $format, ExportEmployeeGetRequest $request)
{
    if (!$employee = Employee::find($employeeId)) {
        throw new ModelNotFoundException();
    }

    return (new EmployeeExporter())->export($employee, $type, $format);
}

    /**
     * Get suggestions for employee roles
     *
     * @param string $companyId
     * @param string|null $value
     * @return JsonResponse
     */
public function suggestRole(string $companyId, ?string $value = null): JsonResponse
{
    if (!($value === null)) {
        $query_array = explode(' ', str_replace(['"', '+', '=', '-', '&&', '||', '>', '<', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '\\', '/'], '?', trim($value)));
        $query_array = array_filter($query_array);
        $query_string = implode(' ', $query_array);
        $value = strtolower($query_string);
    }

        $roleSuggestions = $this->employeeService->suggestRole($value);

        return response()->json(['suggestions' => $roleSuggestions ?? []]);
}

    /**
     * Upload employee file
     *
     * @param string $companyId
     * @param string $employeeId
     * @param EmployeeUploadFileRequest $employeeUploadFileRequest
     * @return AnonymousResourceCollection
     */
public function fileUpload(string $companyId, string $employeeId, EmployeeUploadFileRequest $employeeUploadFileRequest): AnonymousResourceCollection
{
    $files = $this->employeeService->addEmployeeFile($employeeId, $employeeUploadFileRequest->allSafe());

    return EmployeeFilesResource::collection($files);
}

    /**
     * Download an employee file
     *
     * @param string $companyId
     * @param string $employeeId
     * @param string $fileId
     * @param DownloadEmployeeRequest $downloadEmployeeRequest
     * @return BinaryFileResponse
     */
public function fileDownload(
    string $companyId,
    string $employeeId,
    string $fileId,
    DownloadEmployeeRequest $downloadEmployeeRequest
): BinaryFileResponse {
    $file = $this->employeeService->fileDownload($employeeId, $fileId);

    return response()->download($file->getPath(), $file->file_name);
}

    /**
     * Export all employees to excel
     *
     * @param string $companyId
     * @param EmployeeGetRequest $request
     * @return BinaryFileResponse
     */
public function exportDataTable(string $companyId, EmployeeGetRequest $request): BinaryFileResponse
{
    $amount = Employee::count();
    $request->merge([
        'amount' => $amount,
        'page' => 0,
    ]);

    $tablePreferenceType = TablePreferenceType::employees();
    if (!empty($request->project)) {
        $tablePreferenceType = TablePreferenceType::project_employees();
    }
    $tablePrefService = App::make(TablePreferenceService::class);
    $tablePrefs = $tablePrefService->getPreferences('users', $tablePreferenceType->getIndex())->getData();
    $columns = $tablePrefs->columns;
    $employees = parent::getAll($companyId, $request, $tablePreferenceType->getIndex());
    $export = new DataTablesExport($employees['data'], $columns);

    return Excel::download($export, 'employees_' . date('Y-m-d') . '.xlsx');
}

    /**
     * Get all active employees for a specific month
     * @param string $companyId
     * @param EmployeeGetMonthRequest $employeeGetMonthRequest
     * @return JsonResponse
     */
public function getActiveEmployees(string $companyId, EmployeeGetMonthRequest $employeeGetMonthRequest): JsonResponse
{
    $amount = $employeeGetMonthRequest->input('amount', 10);
    $offset = $employeeGetMonthRequest->input('page', 0);
    $employees = $this->employeeService->getActiveEmployees($employeeGetMonthRequest->allSafe());
    return response()->json(['data' => EmployeeAssignResource::collection($employees->sortBy('employee.last_name')
        ->skip($amount * $offset)->take($amount)), 'count' => $employees->count()]);
}

public function editProjectHours(string $companyId, EmployeeEditProjectHoursRequest $employeeEditProjectHoursRequest): JsonResponse
{
    $message = $this->employeeService->editProjectHours($companyId, $employeeEditProjectHoursRequest->allSafe());

    return response()->json($message);
}

public function deleteProjectHours(string $companyId, EmployeeDeleteProjectHoursRequest $employeeDeleteProjectHoursRequest): JsonResponse
{
    $message = $this->employeeService->deleteProjectHours($companyId, $employeeDeleteProjectHoursRequest->allSafe());

    return response()->json($message);
}

public function fileDelete(string $companyId, string $employeeId, string $fileId, DownloadEmployeeRequest $downloadEmployeeRequest): AnonymousResourceCollection
{
    $files = $this->employeeService->deleteFile($employeeId, $fileId);

    return EmployeeFilesResource::collection($files);
}
}
