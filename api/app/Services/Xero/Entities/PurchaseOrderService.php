<?php


namespace App\Services\Xero\Entities;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\GlobalTaxRateRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Enums\EntityPenaltyType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Company;
use App\Models\PurchaseOrder as PurchaseOrderModel;
use App\Models\Resource;
use App\Services\EmployeeService;
use App\Services\Xero\XeroService;
use App\Services\XeroEntityStorageService;
use Illuminate\Support\Facades\App;
use App\Models\TaxRate;
use Tenancy\Facades\Tenancy;
use XeroAPI\XeroPHP\Models\Accounting\LineItem;
use \XeroAPI\XeroPHP\Models\Accounting\PurchaseOrder;
use \XeroAPI\XeroPHP\Models\Accounting\Contact;
use \XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\Models\Accounting\PurchaseOrders;
use App\Services\ResourceService as ResourceModelService;

class PurchaseOrderService extends BaseEntity
{

    protected GlobalTaxRateRepositoryInterface $globalTaxRateRepository;
    protected CompanyRepositoryInterface $companyRepository;
    private PurchaseOrder $accounting;
    private string $tenant_id;
    private AccountingApi $apiInstance;
    private ContactService $contactService;
    private ItemService $itemService;
    private ResourceModelService $resourceService;
    private XeroEntityStorageService $xeroStorageService;

    public function __construct(AccountingApi $apiInstance, string $tenant_id)
    {
        $this->accounting     = new PurchaseOrder;
        $this->tenant_id      = $tenant_id;
        $this->apiInstance    = $apiInstance;
        $this->contactService = new ContactService($this->apiInstance, $this->tenant_id);
        $this->itemService    = new ItemService($this->apiInstance, $this->tenant_id);
        $this->resourceService = App::make(ResourceModelService::class);
        $this->globalTaxRateRepository = App::make(GlobalTaxRateRepositoryInterface::class);
        $this->companyRepository = App::make(CompanyRepositoryInterface::class);
        $this->xeroStorageService = App::make(XeroEntityStorageService::class);
    }

    public function create(PurchaseOrderModel $purchaseOrderModel, string $companyId)
    {
        $arr_purchaseorders = $this->createPurchaseOrderObject($purchaseOrderModel, $companyId);
        $purchaseOrders = new PurchaseOrders;
        $purchaseOrders->setPurchaseOrders($arr_purchaseorders);

        return $this->apiInstance->createPurchaseOrders($this->tenant_id, $purchaseOrders, true);
    }

    public function update(PurchaseOrderModel $purchaseOrderModel, string $companyId)
    {
        $arr_purchaseorders = $this->createPurchaseOrderObject($purchaseOrderModel, $companyId);
        $purchaseOrders = new PurchaseOrders;
        $purchaseOrders->setPurchaseOrders($arr_purchaseorders);

        return $this->apiInstance->updatePurchaseOrder($this->tenant_id, $purchaseOrderModel->xero_id, $purchaseOrders);
    }

    private function setContact($contact_id)
    {

        $contact = new Contact;

        if ($contact_id) {
            $contact->setContactId($contact_id);
        } else {
            $purchaseOrderXero = $this->contactService->createEmpty();
            $contact->setContactId($purchaseOrderXero[0]->getContactId());
        }
        return $contact;
    }

    private function createPurchaseOrderObject(PurchaseOrderModel $purchaseOrderModel, string $companyId): array
    {
        $company = $this->companyRepository->firstById($companyId);
        Tenancy::setTenant($company);

        $account = '429';
        $resource = $this->resourceService->findBorrowedResource($purchaseOrderModel->resource_id);
        if (!$resource) {
            $employeeService = App::make(EmployeeService::class);
            $resource = $employeeService->findBorrowedEmployee($purchaseOrderModel->resource_id);
        }
        $country = $resource ? $resource->country : 0;
        $taxType = 'INPUT';
        $taxIsApplicable = checkIfTaxesAreApplicable($purchaseOrderModel, $country, true);

        if ($taxIsApplicable) {
            $taxRate = $this->globalTaxRateRepository->getTaxRateForPeriod($purchaseOrderModel->legal_entity_id, $purchaseOrderModel->date);
            $taxType = $taxRate->xero_purchase_tax_type ?? 'INPUT';
        }

        $totalNoMods = entityPrice(PurchaseOrderModel::class, $purchaseOrderModel->id, true);
        $lineItems =  $this->itemService->getLines(
            $purchaseOrderModel->items->sortBy('order'),
            $purchaseOrderModel->priceModifiers,
            $purchaseOrderModel->manual_input,
            $totalNoMods,
            1,
            $account,
            $taxType
        );

        if ($purchaseOrderModel->penalty) {
            $line = App::make(LineItem::class);
            $symbol = ' %';
            if ($purchaseOrderModel->penalty == EntityPenaltyType::fixed()->getIndex()) {
                $symbol = CurrencyCode::make($purchaseOrderModel->currency_code)->__toString();
                $penalty = $purchaseOrderModel->penalty;
            } else {
                $penalty = $totalNoMods * ($purchaseOrderModel->penalty/100);
            }
            $line->setDescription('Penalty:  -' . $purchaseOrderModel->penalty . $symbol)
              ->setQuantity(1)
              ->setUnitAmount(0  - $penalty)
              ->setAccountCode($account)
              ->setTaxType($taxType);

            array_push($lineItems, $line);
        }
        $contact = $this->xeroStorageService->getXeroLinkOfEntity(
            $resource->id,
            $purchaseOrderModel->legal_entity_id,
            Resource::class
        );

        if (!$contact) {
            $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
            $legalEntity = $legalEntityRepository->firstById($purchaseOrderModel->legal_entity_id);
            $xeroService = App::makeWith(XeroService::class, ['legalEntity' => $legalEntity]);
            $newResource = $xeroService->createResource($resource, $company->id);
            $contact = $this->setContact($newResource->xero_id ?? null);
        } else {
            $contact = $this->setContact($contact->xero_id ?? null);
        }
        $status = PurchaseOrderStatus::make($purchaseOrderModel->status)->getName();

        if (PurchaseOrderStatus::isPaid($purchaseOrderModel->status)) {
            $status = PurchaseOrderStatus::billed()->getName();
        }

        if (PurchaseOrderStatus::isCancelled($purchaseOrderModel->status)) {
            $status = 'DELETED';
        }

        $arr_purchaseorders = [];
        $purchaseOrder = $this->accounting
          ->setDate($purchaseOrderModel->date)
          ->setDeliveryDate($purchaseOrderModel->delivery_date)
          ->setStatus($status)
          ->setPurchaseOrderNumber($purchaseOrderModel->number.$this->getRandNum())
          ->setReference($purchaseOrderModel->reference)
          ->setCurrencyCode(CurrencyCode::make($resource->default_currency)->getName())
          ->setTotal($purchaseOrderModel->manual_price)
          ->setTotalTax($purchaseOrderModel->manual_vat)
          ->setContact($contact)
          ->setLineItems($lineItems);
        array_push($arr_purchaseorders, $purchaseOrder);

        return $arr_purchaseorders;
    }
}
