<?php

namespace App\Http\Controllers\Resource;

use App\Enums\TablePreferenceType;
use App\Enums\UserRole;
use App\Exports\DataTablesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Export\ExportResourceGetRequest;
use App\Http\Requests\Import\FinalizeImportFileRequest;
use App\Http\Requests\Import\ImportFileCreateRequest;
use App\Http\Requests\Resource\DownloadPurchaseOrderRequest;
use App\Http\Requests\Resource\DownloadResourceRequest;
use App\Http\Requests\Resource\ResourceCreateServicesRequest;
use App\Http\Requests\Resource\ResourceCreateRequest;
use App\Http\Requests\Resource\ResourceDownloadInvoiceRequest;
use App\Http\Requests\Resource\ResourceGetRequest;
use App\Http\Requests\Resource\ResourceImportInvoiceRequest;
use App\Http\Requests\Resource\ResourcesGetRequest;
use App\Http\Requests\Resource\ResourceUpdateRequest;
use App\Http\Requests\Resource\ResourceUpdateServiceRequest;
use App\Http\Resources\PurchaseOrder\ResourcePurchaseOrderResource;
use App\Http\Resources\Resource\ResourceResource;
use App\Http\Resources\Resource\ResourceServiceResource;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Resource;
use App\Models\TablePreference;
use App\Services\Export\ResourceExporter;
use App\Services\Imports\ResourceImportService;
use App\Services\ResourceService;
use App\Services\TablePreferenceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\UnauthorizedException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ResourceController extends Controller
{
    protected ResourceService $resourceService;
    protected string $model = Resource::class;
    protected ResourceImportService $resourceImportService;

    public function __construct(ResourceService $resourceService, ResourceImportService $resourceImportService)
    {
        $this->resourceService = $resourceService;
        $this->resourceImportService = $resourceImportService;
    }

    /**
     * Get a resource of a specific company
     * @param ResourceGetRequest $request
     * @param $company_id
     * @param $resource_id
     * @return ResourceResource
     */
    public function index(ResourceGetRequest $request, $company_id, $resource_id): ResourceResource
    {
        $resource = $this->resourceService->get($resource_id);
        return ResourceResource::make($resource);
    }

    /**
     * Get a resource list of a specific company
     * @param string $companyId
     * @param ResourceGetRequest $request
     * @param null $entity
     * @return array
     */
    public function all(string $companyId, ResourceGetRequest $request, $entity = null): array
    {
        return parent::getAll($companyId, $request, TablePreferenceType::resources()->getIndex());
    }


    /**
     * Create a new resource of a specific company
     * @param ResourceCreateRequest $resourceCreateRequest
     * @return ResourceResource
     */
    public function create(ResourceCreateRequest $resourceCreateRequest): ResourceResource
    {
        $resource = $this->resourceService->create($resourceCreateRequest->validated());

        return ResourceResource::make($resource);
    }

    /**
     * Update a resource of a specific company
     * @param ResourceUpdateRequest $resourceUpdateRequest
     * @param $companyId
     * @param $resourceId
     * @return ResourceResource
     */
    public function update(
        ResourceUpdateRequest $resourceUpdateRequest,
        string $companyId,
        string $resourceId
    ): ResourceResource {
        $fields = $resourceUpdateRequest->validated();
        if ((auth()->user() instanceof Resource)) {
            unset($fields['legal_entity_id']);
            ;
        }
        $resource = $this->resourceService->update($fields, $resourceId);

        return ResourceResource::make($resource->load('media'));
    }

    /**
     * Get certain users of a specific company using autocomplete
     * @param $company_id
     * @param $value
     * @param Request $request
     * @return JsonResponse
     */
    public function suggest($company_id, Request $request, $value = null): JsonResponse
    {
        if (UserRole::isSales(auth()->user()->role)) {
            throw new UnauthorizedException();
        }
        return $this->resourceService->suggest($company_id, $request, $value);
    }

    /**
     * Upload import file for resource of a specific company
     * @param $company_id
     * @param ImportFileCreateRequest $request
     * @return
     */
    public function uploadImportFile(ImportFileCreateRequest $request, $company_id)
    {
        return $this->resourceImportService->saveFile($request);
    }

    /**
     * Import  data from file to resource table of a specific company
     * @param $company_id
     * @param FinalizeImportFileRequest $request
     * @return
     */
    public function finalizeImportFile(FinalizeImportFileRequest $request, $company_id)
    {
        $import = $this->resourceImportService->import($request);

        if (count($import->importData) == 0) {
            throw new BadRequestHttpException();
        }

        $response = parent::getAll($company_id, $request, TablePreferenceType::resources()->getIndex());
        if ($import->haveImportError) {
            $response['notValidFileRows'] = $import->notValidFileRows;
        }
        return $response;
    }

    /**
     * Export data from resource
     * @param ExportResourceGetRequest $request
     * @param $company_id
     * @param $resource_id
     * @param $type
     * @param $format
     */

    public function export(ExportResourceGetRequest $request, $company_id, $resource_id, $type, $format)
    {
        if (!$resource = Resource::find($resource_id)) {
            throw new ModelNotFoundException();
        }
        return (new ResourceExporter())->export($resource, $type, $format);
    }

    /**
     * Get suggest jub title of resource of a specific company
     * @param $company_id
     * @param null $value
     * @return JsonResponse
     */
    public function suggestJobTitle($company_id, $value = null): JsonResponse
    {
        if (UserRole::isSales(auth()->user()->role)) {
            throw new UnauthorizedException();
        }

        return $this->resourceService->suggestJobTitle($company_id, $value);
    }

    /**
     * @param ResourceImportInvoiceRequest $request
     * @param $company_id
     * @param $resource_id
     * @param $purchase_order_id
     * @return ResourcePurchaseOrderResource
     */
    public function uploadInvoice(ResourceImportInvoiceRequest $request, $company_id, $resource_id, $purchase_order_id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($purchase_order_id);
        $purchaseOrder = $this->resourceService->uploadInvoice($purchaseOrder, $request);
        $purchaseOrder->company_id = $company_id;

        return ResourcePurchaseOrderResource::make($purchaseOrder);
    }

    public function downloadInvoice(ResourceDownloadInvoiceRequest $request, $company_id, $resource_id, $purchase_order_id, $invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id);

        if ($invoice->purchase_order_id === $purchase_order_id) {
            return $this->resourceService->downloadInvoice($invoice);
        }

        throw new ModelNotFoundException();
    }

    /**
     * Create multiple services for a specific resource
     *
     * @param string $companyId
     * @param string $resourceId
     * @param ResourceCreateServicesRequest $request
     * @return AnonymousResourceCollection
     */
    public function createServices(
        string $companyId,
        string $resourceId,
        ResourceCreateServicesRequest $request
    ): AnonymousResourceCollection {
        $services = $this->resourceService->createServices($resourceId, $request->allSafe());

        return ResourceServiceResource::collection($services);
    }

    public function updateService(
        string $companyId,
        string $resourceId,
        string $serviceId,
        ResourceUpdateServiceRequest $request
    ): ResourceServiceResource {
        $service = $this->resourceService->updateService($resourceId, $serviceId, $request->allSafe());

        return ResourceServiceResource::make($service);
    }

    /**
     * Download a employee contract
     *
     * @param string $companyId
     * @param string $resourceId
     * @param DownloadResourceRequest $downloadResourceRequest
     * @return BinaryFileResponse
     */
    public function contractDownload(
        string $companyId,
        string $resourceId,
        DownloadResourceRequest $downloadResourceRequest
    ): BinaryFileResponse {
        $contract = $this->resourceService->contractDownload($resourceId);

        return response()->download($contract->getPath(), $contract->file_name);
    }

    /**
     * @param string $companyId
     * @param string $resourceId
     * @param string $purchaseOrderId
     * @param string $type
     * @param DownloadPurchaseOrderRequest $purchaseOrderRequest
     *
     * @return \Illuminate\Http\Response|BinaryFileResponse
     */
    public function downloadPurchaseOrder(
        string $companyId,
        string $resourceId,
        string $purchaseOrderId,
        string $templateId,
        string $type,
        DownloadPurchaseOrderRequest $purchaseOrderRequest
    ) {
        return $this->resourceService->downloadPurchaseOrder($resourceId, $purchaseOrderId, $templateId, $type);
    }

    /**
     * Export all resources to excel
     *
     * @param string $companyId
     * @param ResourcesGetRequest $request
     * @return BinaryFileResponse
     */
    public function exportDataTable(string $companyId, ResourcesGetRequest $request): BinaryFileResponse
    {
        $amount = Resource::count();
        $request->merge([
          'amount' => $amount,
          'page' => 0,
        ]);

        $tablePrefService = App::make(TablePreferenceService::class);
        $tablePrefs = $tablePrefService->getPreferences('users', TablePreferenceType::resources()->getIndex())->getData();
        $columns = $tablePrefs->columns;
        $resources = parent::getAll($companyId, $request, TablePreferenceType::resources()->getIndex());
        $export = new DataTablesExport($resources['data'], $columns);

        return Excel::download($export, 'resources_' . date('Y-m-d') . '.xlsx');
    }
}
