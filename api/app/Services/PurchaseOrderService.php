<?php


namespace App\Services;


use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use App\DTO\PurchaseOrders\CreatePurchaseOrderDTO;
use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Requests\BaseRequest;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Repositories\PurchaseOrderRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PurchaseOrderService
{
    protected PurchaseOrderRepository $purchase_repository;
    protected PurchaseOrderRepositoryInterface $purchaseOrderRepository;

    private LegalEntityRepositoryInterface $legalEntityRepository;
    private LegalEntitySettingRepositoryInterface $legalSettingRepository;

    public function __construct(
        PurchaseOrderRepository $purchase_repository,
        PurchaseOrderRepositoryInterface $purchaseOrderRepository,
        LegalEntityRepositoryInterface $legalEntityRepository,
        LegalEntitySettingRepositoryInterface $legalSettingRepository
    ) {
        $this->purchase_repository = $purchase_repository;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->legalEntityRepository = $legalEntityRepository;
        $this->legalSettingRepository = $legalSettingRepository;
    }

    public function create(string $projectId, CreatePurchaseOrderDTO $createPurchaseOrderDTO): PurchaseOrder
    {
        if ($createPurchaseOrderDTO->legal_entity_id) {
            $legalEntity = $this->legalEntityRepository->firstById($createPurchaseOrderDTO->legal_entity_id);
            $format = $this->legalSettingRepository->firstById($legalEntity->legal_entity_setting_id);
            $this->legalSettingRepository->update($format->id, ['purchase_order_number' => $format->purchase_order_number + 1]);
            $number = transformFormat($format->purchase_order_number_format, $format->purchase_order_number + 1);
        } else {
            $number = getPurchaseOrderDraftNumber();
        }

        if (!$createPurchaseOrderDTO->vat_status) {
            $createPurchaseOrderDTO->vat_status = VatStatus::default()->getIndex();
        }

        $purchaseOrder = $this->purchaseOrderRepository->create(
            array_merge($createPurchaseOrderDTO->toArray(), [
              'project_id' => $projectId,
              'number' => $number,
              'status' => PurchaseOrderStatus::draft()->getIndex(),
              'created_by' => auth()->user()->id,
            ])
        );


        return $purchaseOrder;
    }

    public function update(PurchaseOrder $purchaseOrder, BaseRequest $request)
    {
        return $this->purchase_repository->update($purchaseOrder, $request->allSafe());
    }

    public function checkAuthorization(): void
    {
        if (UserRole::isSales(auth()->user()->role) || UserRole::isHr(auth()->user()->role) ||
          UserRole::isOwner_read(auth()->user()->role)) {
            throw new UnauthorizedException();
        }
    }

    public function checkIsDraft(string $purchaseOrderId): void
    {
        $po = PurchaseOrder::findOrFail($purchaseOrderId);
        if (!PurchaseOrderStatus::isDraft($po->status)) {
            throw new UnprocessableEntityHttpException(
                'Adding, updating or deleting items is only allowed on purchase orders with status Draft.'
            );
        }
    }

    public function getPurchaseOrdersForExternalResource(string $resourceId): array
    {
        $query = [
          'query' => [
              'bool' => [
                  'must' => [
                      ['match' => ['resource_id' => $resourceId]],
                      ['terms' => ['status' => [PurchaseOrderStatus::authorised()->getIndex(),
                          PurchaseOrderStatus::billed()->getIndex(),
                          PurchaseOrderStatus::paid()->getIndex()]]]
                  ]
              ]
          ]
        ];

        $result = PurchaseOrder::searchAllTenantsQuery('purchase_orders', $query);

        return array_map(function ($r) {
            $po = App::make(PurchaseOrder::class);
            $po->id = $r['_source']['id'];
            $po->project_id = $r['_source']['project_id'];
            $po->resource_id = $r['_source']['resource_id'];
            $po->resource = $r['_source']['resource'];
            $po->date = $r['_source']['date'];
            $po->delivery_date = $r['_source']['delivery_date'];
            $po->status = $r['_source']['status'];
            $po->company_id = $r['_source']['company_id'];
            $po->number = $r['_source']['number'];
            $po->manual_price = $r['_source']['manual_price'];
            $po->manual_vat = $r['_source']['manual_vat'];
            $po->currency_code = $r['_source']['currency_code'];

            return $po;
        }, $result['hits']['hits']);
    }

    public function checkIsPmRestricted(string $projectId)
    {
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($projectId);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }
    }

    public function createIndependent(CreatePurchaseOrderDTO $createPurchaseOrderDTO): PurchaseOrder
    {
        if ($createPurchaseOrderDTO->legal_entity_id) {
            $legalEntity = $this->legalEntityRepository->firstById($createPurchaseOrderDTO->legal_entity_id);
            $format = $this->legalSettingRepository->firstById($legalEntity->legal_entity_setting_id);
            $this->legalSettingRepository->update($format->id, ['purchase_order_number' => $format->purchase_order_number + 1]);
            $number = transformFormat($format->purchase_order_number_format, $format->purchase_order_number + 1);
        } else {
            $number = getPurchaseOrderDraftNumber();
        }

        if (!$createPurchaseOrderDTO->vat_status) {
            $createPurchaseOrderDTO->vat_status = VatStatus::default()->getIndex();
        }

        $employeeService = App::make(EmployeeService::class);
        $contractor = $employeeService->findBorrowedEmployee($createPurchaseOrderDTO->resource_id);
        if (!$contractor) {
            $resourceService = App::make(ResourceService::class);
            $contractor = $resourceService->findBorrowedResource($createPurchaseOrderDTO->resource_id);
        }
        $project = Project::where([['name', $contractor->name], ['purchase_order_project', true]])->first();
        if (!$project) {
            $project = Project::create(['name' => $contractor->name , 'purchase_order_project' => true]);
        }

        return $this->purchaseOrderRepository->create(
            array_merge($createPurchaseOrderDTO->toArray(), [
              'project_id' => $project->id,
              'number' => $number,
              'status' => PurchaseOrderStatus::draft()->getIndex(),
              'created_by' => auth()->user()->id,
            ])
        );
    }
}
