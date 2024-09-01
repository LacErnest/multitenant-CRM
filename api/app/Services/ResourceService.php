<?php

namespace App\Services;

use App\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use App\Contracts\Repositories\ResourceRepositoryInterface;
use App\Enums\PurchaseOrderStatus;
use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use App\Http\Requests\Resource\ResourceCreateRequest;
use App\Http\Requests\Resource\ResourceImportInvoiceRequest;
use App\Http\Requests\Resource\ResourceUpdateRequest;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Resource;
use App\Models\Service;
use App\Repositories\ResourceRepository;
use App\Services\Export\PurchaseOrderExporter;
use Elasticquent\ElasticquentCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ResourceService
{
    protected ResourceRepository $resourceRepository;

    protected ResourceRepositoryInterface $resourceRepositoryInterface;

    public function __construct(
        ResourceRepository $resourceRepository,
        ResourceRepositoryInterface $resourceRepositoryInterface
    ) {
        $this->resourceRepository = $resourceRepository;
        $this->resourceRepositoryInterface = $resourceRepositoryInterface;
    }

    public function get($resource_id)
    {
        return $this->resourceRepository->get($resource_id);
    }

    public function create(array $resourceAttributes): Resource
    {
        $randomResource = $this->resourceRepositoryInterface->firstByOrNull('status', ResourceStatus::active()->getIndex());
        if ($randomResource) {
            $resourceAttributes['can_be_borrowed'] = $randomResource->can_be_borrowed;
        }

        $resource = $this->resourceRepository->create($resourceAttributes);
        $this->addContractToResource($resource, $resourceAttributes);

        return $resource;
    }

    public function update(array $resourceAttributes, string $resourceId): Resource
    {
        $resource = $this->resourceRepository->update($resourceAttributes, $resourceId);
        $this->addContractToResource($resource, $resourceAttributes);

        return $resource;
    }

    public function suggest($company_id, Request $request, $value)
    {
        return $this->resourceRepository->suggest($company_id, $request, $value);
    }

    public function suggestJobTitle($company_id, $value)
    {
        return $this->resourceRepository->suggestJobTitle($company_id, $value);
    }

    public function uploadInvoice(PurchaseOrder $purchaseOrder, ResourceImportInvoiceRequest $request)
    {
        return $this->resourceRepository->uploadInvoice($purchaseOrder, $request->allSafe());
    }

    public function downloadInvoice(Invoice $invoice)
    {
        return $this->resourceRepository->downloadInvoice($invoice);
    }

    public function createServices(
        string $resourceId,
        array $services
    ): ElasticquentCollection {
        $resource = Resource::find($resourceId);
        if (!$resource) {
            $resource = Employee::find($resourceId);
        }
        $this->resourceRepository->createServices($resourceId, $services['services']);

        return $resource->services;
    }

    public function updateService(
        string $resourceId,
        string $serviceId,
        array $attributes
    ): Service {
        return $this->resourceRepository->updateService($resourceId, $serviceId, $attributes);
    }

    public function contractDownload(string $resourceId)
    {
        $resource = $this->resourceRepositoryInterface->firstById($resourceId);
        if (ResourceType::isFreelancer($resource->type)) {
            $contract = $resource->getMedia('contract')->first();

            if ($contract) {
                return $contract;
            }
        }

        throw new UnprocessableEntityHttpException('No contract found for this resource.');
    }

    private function addContractToResource(Resource $resource, array $attributes): void
    {
        if (Arr::exists($attributes, 'contract_file')) {
            $doc = Arr::get($attributes, 'contract_file', false);
            if ($doc && ResourceType::isFreelancer($resource->type)) {
                $resource->addMediaFromBase64($attributes['contract_file'])
                ->usingFileName('contract_' . $resource->name . '.' . getDocumentMimeType($doc))
                ->toMediaCollection('contract');
            } else {
                if ($contract = $resource->getMedia('contract')->first()) {
                    $contract->delete();
                }
            }
        }
    }

    public function downloadPurchaseOrder(string $resourceId, string $purchaseOrderId, string $templateId, string $type)
    {
        if (!in_array($type, ['docx','pdf'])) {
            throw new UnprocessableEntityHttpException('Wrong type of data.');
        }

        $purchaseOrderRepository = App::make(PurchaseOrderRepositoryInterface::class);
        $purchaseOrder = $purchaseOrderRepository->firstById($purchaseOrderId);
        if ($purchaseOrder->resource_id != $resourceId) {
            throw new UnprocessableEntityHttpException('This purchase order does not belong to this resource.');
        }

        $isAuthorised = PurchaseOrderStatus::isAuthorised($purchaseOrder->status);
        $isBilled = PurchaseOrderStatus::isBilled($purchaseOrder->status);
        $isPaid = PurchaseOrderStatus::isPaid($purchaseOrder->status);

        if ($isAuthorised || $isBilled || $isPaid) {
            return (new PurchaseOrderExporter())->export($purchaseOrder, $templateId, $type);
        }

        throw new UnprocessableEntityHttpException('This purchase order cannot be downloaded.');
    }

    public function findBorrowedResource(string $resourceId): ?Resource
    {
        $query = [
          'query' => [
              'bool' => [
                  'must' => [
                      'match' => ['id' => $resourceId]
                  ]
              ]
          ]
        ];

        $result = Resource::searchAllTenantsQuery('resources', $query);

        if (!empty($result['hits']['hits'])) {
            $resource = App::make(Resource::class);
            $resource->id = $result['hits']['hits'][0]['_source']['id'];
            $resource->default_currency = $result['hits']['hits'][0]['_source']['default_currency'];
            $resource->daily_rate = $result['hits']['hits'][0]['_source']['daily_rate'];
            $resource->hourly_rate = $result['hits']['hits'][0]['_source']['hourly_rate'];
            $resource->can_be_borrowed = $result['hits']['hits'][0]['_source']['can_be_borrowed'];
            $resource->name = $result['hits']['hits'][0]['_source']['name'];
            $resource->first_name = $result['hits']['hits'][0]['_source']['first_name'];
            $resource->last_name = $result['hits']['hits'][0]['_source']['last_name'];
            $resource->type = $result['hits']['hits'][0]['_source']['type'];
            $resource->status = $result['hits']['hits'][0]['_source']['status'];
            $resource->email = $result['hits']['hits'][0]['_source']['email'];
            $resource->phone_number = $result['hits']['hits'][0]['_source']['phone_number'];
            $resource->company_id = $result['hits']['hits'][0]['_source']['company_id'];
            $resource->country = $result['hits']['hits'][0]['_source']['country'];
            $resource->tax_number = $result['hits']['hits'][0]['_source']['tax_number'];
            $resource->addressline_1 = $result['hits']['hits'][0]['_source']['addressline_1'];
            $resource->addressline_2 = $result['hits']['hits'][0]['_source']['addressline_2'];
            $resource->city = $result['hits']['hits'][0]['_source']['city'];
            $resource->region = $result['hits']['hits'][0]['_source']['region'];
            $resource->postal_code = $result['hits']['hits'][0]['_source']['postal_code'];
            $resource->non_vat_liable = array_key_exists('non_vat_liable', $result['hits']['hits'][0]['_source']) ? $result['hits']['hits'][0]['_source']['non_vat_liable'] : false;

            return $resource;
        }

        return null;
    }
}
