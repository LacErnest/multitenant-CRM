<?php


namespace App\Services\Xero\Entities;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\GlobalTaxRateRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Company;
use App\Models\TaxRate;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\App;
use App\Models\Customer;
use App\Models\Resource;
use App\Services\Xero\XeroService;
use App\Services\XeroEntityStorageService;
use Tenancy\Facades\Tenancy;
use \XeroAPI\XeroPHP\Models\Accounting\Invoice;
use \XeroAPI\XeroPHP\Models\Accounting\Contact;
use \XeroAPI\XeroPHP\Api\AccountingApi;
use App\Models\Invoice as InvoiceModel;
use App\Services\ResourceService as ResourceModelService;
use XeroAPI\XeroPHP\Models\Accounting\Invoices;
use App\Models\Contact as ContactModel;

class InvoiceService extends BaseEntity
{

    protected GlobalTaxRateRepositoryInterface $globalTaxRateRepository;
    protected CompanyRepositoryInterface $companyRepository;
    private Invoice $accounting;
    private string $tenant_id;
    private AccountingApi $apiInstance;
    private ContactService $contactService;
    private ItemService $itemService;
    private XeroEntityStorageService $xeroStorageService;
    private ResourceModelService $resourceService;

    public function __construct(AccountingApi $apiInstance, string $tenant_id)
    {
        $this->accounting     = new Invoice;
        $this->tenant_id      = $tenant_id;
        $this->apiInstance    = $apiInstance;
        $this->contactService = new ContactService($this->apiInstance, $this->tenant_id);
        $this->itemService    = new ItemService($this->apiInstance, $this->tenant_id);
        $this->resourceService = App::make(ResourceModelService::class);
        $this->globalTaxRateRepository = App::make(GlobalTaxRateRepositoryInterface::class);
        $this->companyRepository = App::make(CompanyRepositoryInterface::class);
        $this->xeroStorageService = App::make(XeroEntityStorageService::class);
    }


    public function getInvoices($invoice_id)
    {
        return $this->apiInstance->getInvoice($this->tenant_id, $invoice_id);
    }


    public function create(InvoiceModel $invoice, string $companyId)
    {
        if ($invoice->type == InvoiceType::accpay()->getIndex() && !$invoice->purchase_order_id) {
            return null;
        }

        $arr_invoices = $this->createInvoiceObject($invoice, $companyId);
        $invoices = new Invoices();
        $invoices->setInvoices($arr_invoices);

        return $this->apiInstance->createInvoices($this->tenant_id, $invoices, true);
    }

    public function update(InvoiceModel $invoice, string $companyId)
    {
        if ($invoice->type == InvoiceType::accpay()->getIndex() && !$invoice->purchase_order_id) {
            return null;
        }

        if (!$invoice->xero_id) {
            return null;
        }

        $arr_invoices = $this->createInvoiceObject($invoice, $companyId);
        $invoices = new Invoices();
        $invoices->setInvoices($arr_invoices);

        return $this->apiInstance->updateInvoice($this->tenant_id, $invoice->xero_id, $invoices);
    }

    private function setContact($contact_id)
    {
        $contact = new Contact;

        if ($contact_id) {
            $contact->setContactId($contact_id);
        } else {
            $contactXero  = $this->contactService->createEmpty();
            $contact->setContactId($contactXero[0]->getContactId());
        }

        return $contact;
    }

    private function createInvoiceObject(InvoiceModel $invoice, string $companyId): array
    {
        $company = $this->companyRepository->firstById($companyId);
        Tenancy::setTenant($company);

        if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
            $price = $invoice->total_price_usd;
            $vat = $invoice->total_vat_usd;
        } else {
            $price = $invoice->total_price;
            $vat = $invoice->total_vat;
        }

        if ($invoice->type == InvoiceType::accpay()->getIndex() && $invoice->purchase_order_id) {
            $account = '429';
            $taxType = 'INPUT';
            $purchaseOrder = $invoice->purchaseOrder;
            $currencyRate = $purchaseOrder->currency_rate_resource;
            $resource = $this->resourceService->findBorrowedResource($purchaseOrder->resource_id);
            if (!$resource) {
                $employeeService = App::make(EmployeeService::class);
                $resource = $employeeService->findBorrowedEmployee($purchaseOrder->resource_id);
            }
            $country = $resource ? $resource->country : 0;
            $taxIsApplicable = checkIfTaxesAreApplicable($purchaseOrder, $country, true);
            $contact = $this->xeroStorageService->getXeroLinkOfEntity(
                $resource->id,
                $purchaseOrder->legal_entity_id,
                Resource::class
            );

            if (!$contact) {
                $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
                $legalEntity = $legalEntityRepository->firstById($purchaseOrder->legal_entity_id);
                $xeroService = App::makeWith(XeroService::class, ['legalEntity' => $legalEntity]);
                $newResource = $xeroService->createResource($purchaseOrder->resource, $company->id);
                $contact = $this->setContact($newResource->xero_id ?? null);
            } else {
                $contact = $this->setContact($contact->xero_id ?? null);
            }
        } else {
            $account = '200';
            $taxType = 'OUTPUT';
            $currencyRate = $invoice->currency_rate_customer;
            $taxIsApplicable = checkIfTaxesAreApplicable($invoice);
            $contact = $this->xeroStorageService->getXeroLinkOfEntity(
                $invoice->project->contact->id,
                $invoice->legal_entity_id,
                ContactModel::class
            );

            if (!$contact) {
                $customer = $this->xeroStorageService->getXeroLinkOfEntity(
                    $invoice->project->contact->customer->id,
                    $invoice->legal_entity_id,
                    Customer::class
                );
                $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
                $legalEntity = $legalEntityRepository->firstById($invoice->legal_entity_id);
                $xeroService = App::makeWith(XeroService::class, ['legalEntity' => $legalEntity]);
                if (!$customer) {
                      $customer = $xeroService->createCustomer($invoice->project->contact->customer, $company->id);
                } else {
                    $xeroService->updateCustomer($invoice->project->contact->customer, $company->id, $customer->xero_id);
                }
                $contact = $this->setContact($customer->xero_id ?? null);
            } else {
                $contact = $this->setContact($contact->xero_id ?? null);
            }
        }

        if ($taxIsApplicable) {
            $taxRate = $this->globalTaxRateRepository->getTaxRateForPeriod($invoice->legal_entity_id, $invoice->date);
            if ($invoice->purchase_order_id) {
                $taxType = $taxRate->xero_purchase_tax_type ?? 'INPUT';
            } else {
                $taxType = $taxRate->xero_sales_tax_type ?? 'OUTPUT';
            }
        }

        $totalNoMods = entityPrice(InvoiceModel::class, $invoice->id, true);
        $lineItems = $this->itemService->getLines(
            $invoice->items->sortBy('order'),
            $invoice->priceModifiers,
            $invoice->manual_input,
            $totalNoMods,
            $currencyRate,
            $account,
            $taxType
        );
        $status = InvoiceStatus::make($invoice->status)->getName();

        if (InvoiceStatus::isApproval($invoice->status)) {
            $status = InvoiceStatus::draft()->getName();
        }

        if (InvoiceStatus::isPaid($invoice->status) || InvoiceStatus::isUnpaid($invoice->status)) {
            $status = InvoiceStatus::authorised()->getName();
        }

        if (InvoiceStatus::isCancelled($invoice->status)) {
            $xeroInvoice = $this->apiInstance->getInvoice($this->tenant_id, $invoice->xero_id);
            $xeroInvoiceStatus = $xeroInvoice[0]->getStatus();
            if ($xeroInvoiceStatus == 'AUTHORISED') {
                $status = 'VOIDED';
            } else {
                $status = 'DELETED';
            }
        }


        $arr_invoices = [];
        $this->accounting
          ->setDate($invoice->date)
          ->setDueDate($invoice->due_date)
          ->setStatus($status)
          ->setReference($invoice->reference)
          ->setCurrencyCode(CurrencyCode::make($invoice->currency_code)->getName())
          ->setCurrencyRate($invoice->currency_rate_customer)
          ->setType(InvoiceType::make($invoice->type)->getName())
          ->setInvoiceNumber($invoice->number)
          ->setTotal($invoice->manual_input ? decFormat($invoice->manual_price) : decFormat(ceiling($price * $currencyRate, 2)))
          ->setTotalTax($invoice->manual_input ? decFormat($invoice->manual_vat) : decFormat($vat * $currencyRate))
          ->setContact($contact)
          ->setLineItems($lineItems);

        array_push($arr_invoices, $this->accounting);

        return $arr_invoices;
    }
}
