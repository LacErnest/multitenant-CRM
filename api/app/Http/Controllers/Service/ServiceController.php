<?php

namespace App\Http\Controllers\Service;

use App\Enums\TablePreferenceType;
use App\Enums\UserRole;
use App\Exports\DataTablesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Resource\ResourceGetServicesRequest;
use App\Http\Requests\Service\ServiceCreateRequest;
use App\Http\Requests\Service\ServiceDeleteRequest;
use App\Http\Requests\Service\ServicesGetRequest;
use App\Http\Requests\Service\ServiceUpdateRequest;
use App\Http\Resources\Service\ServiceResource;
use App\Models\Service;
use App\Models\TablePreference;
use App\Services\ServiceService;
use App\Services\TablePreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\UnauthorizedException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServiceController extends Controller
{
    protected ServiceService $service;

    protected string $model = Service::class;

    public function __construct(ServiceService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all service of a specific company
     * @param string $companyId
     * @param Request $request
     * @param null $entity
     * @return array
     */
    public function all(string $companyId, Request $request, $entity = null): array
    {
        if (!UserRole::isHr(auth()->user()->role)) {
            return parent::getAll($companyId, $request, TablePreferenceType::services()->getIndex());
        }
        throw new UnauthorizedException();
    }

    /**
     * Get one service of a specific company
     * @param $company_id
     * @param $service_id
     * @return ServiceResource
     */
    public function index($company_id, $service_id): ServiceResource
    {
        if (!UserRole::isHr(auth()->user()->role)) {
            return parent::getSingle($company_id, $service_id);
        }
        throw new UnauthorizedException();
    }

    /**
     * Get certain services of a specific company using autocomplete
     * @param $company_id
     * @param $value
     * @param Request $request
     * @return JsonResponse
     */
    public function suggest($company_id, Request $request, $value = null): JsonResponse
    {
        return $this->service->suggest($value, $request->all());
    }

    /**
     * Create a new service of a specific company
     * @param $company_id
     * @param ServiceCreateRequest $request
     * @return ServiceResource
     */
    public function create($company_id, ServiceCreateRequest $request): ServiceResource
    {
        $service = $this->service->create($request);

        return ServiceResource::make($service);
    }

    /**
     * Update a service of a specific company
     * @param $company_id
     * @param $service_id
     * @param ServiceUpdateRequest $request
     * @return ServiceResource
     */
    public function update($company_id, $service_id, ServiceUpdateRequest $request): ServiceResource
    {
        $service = $this->service->update($service_id, $request);

        return ServiceResource::make($service);
    }

    /**
     * Delete a service of a specific company
     * @param $company_id
     *
     * @param ServiceDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($company_id, ServiceDeleteRequest $request): JsonResponse
    {
        $status = $this->service->delete($request->all());

        return response()->json(['message' => $status . ' service(s) deleted successfully.']);
    }

    /**
     * @param string $companyId
     * @param string $resourceId
     * @param ResourceGetServicesRequest $request
     * @param null $entity
     * @return array
     */
    public function getAllServices(
        string $companyId,
        string $resourceId,
        ResourceGetServicesRequest $request,
        $entity = null
    ): array {
        $request['resource'] = $resourceId;

        return parent::getAll($companyId, $request, TablePreferenceType::resource_services()->getIndex());
    }

    /**
     * Export all services to excel
     *
     * @param string $companyId
     * @param ServicesGetRequest $request
     * @return BinaryFileResponse
     */
    public function exportDataTable(string $companyId, ServicesGetRequest $request): BinaryFileResponse
    {
        $amount = Service::count();
        $request->merge([
          'amount' => $amount,
          'page' => 0,
        ]);

        $tablePrefService = App::make(TablePreferenceService::class);
        $tablePrefs = $tablePrefService->getPreferences('users', TablePreferenceType::services()->getIndex())->getData();
        $columns = $tablePrefs->columns;
        $services = parent::getAll($companyId, $request, TablePreferenceType::services()->getIndex());
        $export = new DataTablesExport($services['data'], $columns);

        return Excel::download($export, 'services_' . date('Y-m-d') . '.xlsx');
    }
}
