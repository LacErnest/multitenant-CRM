<?php


namespace App\Services\Xero;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\LegalEntity;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Resource;
use App\Services\Xero\Entities\CreditNoteService;
use App\Services\Xero\Entities\CustomerService;
use App\Services\Xero\Entities\PurchaseOrderService;
use App\Services\Xero\Entities\QuoteService;
use App\Services\Xero\Entities\ResourceService;
use App\Services\Xero\Entities\TaxRateService;
use App\Services\XeroEntityStorageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tenancy\Facades\Tenancy;
use XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\Api\IdentityApi;
use Illuminate\Support\Facades\App;
use XeroAPI\XeroPHP\ApiException;
use XeroAPI\XeroPHP\Configuration;
use GuzzleHttp\Client;
use App\Services\Xero\Entities\InvoiceService;
use App\Services\Xero\Auth\AuthService;

class XeroService
{
    protected ?LegalEntity $legalEntity;
    //Credentials
    private ?string $accessToken;
    private ?string $tenantId;

    //Api Instance
    private IdentityApi $xero;
    private Configuration $config;
    private AccountingApi $apiInstance;
    private AuthService $authService;

    //Services
    private InvoiceService $invoiceService;
    private QuoteService $quoteService;
    private CustomerService $customerService;
    private ResourceService $resourceService;
    private PurchaseOrderService $purchaseOrderService;
    private CreditNoteService $creditNoteService;
    private TaxRateService $taxRateService;
    private XeroEntityStorageService $xeroStorageService;

    public function __construct(LegalEntity $legalEntity = null)
    {
        $this->authService = new AuthService($legalEntity);

        if (!$this->authService->exists()) {
            throw new \Exception('`tenancy` credentials not exists');
        }

        $this->legalEntity = $legalEntity;
        $this->createCredentials($legalEntity);
        $this->createApiInstance();
        $this->setEntities();
        $this->xeroStorageService = App::make(XeroEntityStorageService::class);
    }

    private function createCredentials($legalEntity = null): void
    {
        $this->accessToken = $this->authService->getAccessToken();
        $this->tenantId = $this->authService->getTenantId();
    }

    private function setEntities(): void
    {
        $this->invoiceService       = App::makeWith(
            InvoiceService::class,
            ['apiInstance' => $this->apiInstance, 'tenant_id' => $this->tenantId]
        );
        $this->quoteService         = App::makeWith(
            QuoteService::class,
            ['apiInstance' => $this->apiInstance, 'tenant_id' => $this->tenantId]
        );
        $this->customerService      = App::makeWith(
            CustomerService::class,
            ['apiInstance' => $this->apiInstance, 'tenant_id' => $this->tenantId]
        );
        $this->resourceService      = App::makeWith(
            ResourceService::class,
            ['apiInstance' => $this->apiInstance, 'tenant_id' => $this->tenantId]
        );
        $this->purchaseOrderService = App::makeWith(
            PurchaseOrderService::class,
            ['apiInstance' => $this->apiInstance, 'tenant_id' => $this->tenantId]
        );
        $this->creditNoteService    = App::makeWith(
            CreditNoteService::class,
            ['apiInstance' => $this->apiInstance, 'tenant_id' => $this->tenantId]
        );
        $this->taxRateService       = App::makeWith(
            TaxRateService::class,
            ['apiInstance' => $this->apiInstance, 'tenant_id' => $this->tenantId]
        );
    }

    private function createApiInstance():void
    {
        if ($this->authService->getHasExpired()) {
            DB::beginTransaction();
            try {
                DB::table('legal_entity_xero_configs')->where('id', $this->legalEntity->legal_entity_xero_config_id)->lockForUpdate();
                try {
                    $this->authService->refreshToken();
                    $this->createCredentials();
                } catch (\Exception $exception) {
                    if ($exception->getCode() == 403) {
                        throw new UnprocessableEntityHttpException('OAuth2 connection to Xero has expired or has been cancelled');
                    } elseif ($exception->getCode() == 401) {
                        $this->authService->refreshToken();
                        $this->createCredentials();
                    } else {
                        logger($exception);
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
            }
        }

        $this->config      = Configuration::getDefaultConfiguration()->setAccessToken($this->accessToken);
        $this->xero        = new IdentityApi(new Client(), $this->config);
        $this->apiInstance = new AccountingApi(new Client(), $this->config);
    }

    public function createInvoice(Invoice $invoice, string $companyId)
    {
        try {
            $xeroInvoice = $this->invoiceService->create($invoice, $companyId);
            logger(json_encode($xeroInvoice));
        } catch (ApiException $e) {
            Log::error($e->getResponseBody());
            $xeroInvoice = null;
        }
        if ($xeroInvoice && $xeroInvoice[0]->getInvoiceId() != '00000000-0000-0000-0000-000000000000') {
            $invoice->xero_id = $xeroInvoice[0]->getInvoiceId();
            $invoice->saveQuietly();
        }
    }

    public function updateInvoice(Invoice $invoice, string $companyId)
    {
        try {
            $xeroInvoice =  $this->invoiceService->update($invoice, $companyId);
            logger($xeroInvoice);
        } catch (ApiException $e) {
            Log::error($e->getResponseBody());
        }
    }

    public function deleteInvoice(Invoice $invoice, string $companyId)
    {
        $this->invoiceService->update($invoice, $companyId);
    }


    public function createQuote(Quote $quote, string $companyId)
    {
        try {
            $xeroQuote = $this->quoteService->create($quote, $companyId);
            logger(json_encode($xeroQuote));
        } catch (ApiException $e) {
            Log::error($e->getResponseBody());
            $xeroQuote = null;
        }
        if ($xeroQuote && $xeroQuote[0]->getQuoteId() != '00000000-0000-0000-0000-000000000000') {
            $quote->xero_id = $xeroQuote[0]->getQuoteId();
            $quote->saveQuietly();
        }
    }

    public function updateQuote(Quote $quote, string $companyId)
    {
        try {
            $xeroQuote = $this->quoteService->update($quote, $companyId);
            logger(json_encode($xeroQuote));
        } catch (ApiException $e) {
            Log::error($e->getResponseBody());
        }
    }

    public function createCustomer(Customer $customer, string $companyId)
    {
        try {
            $xeroCustomer = $this->customerService->create($customer, $companyId);
            logger(json_encode($xeroCustomer));
        } catch (ApiException $e) {
            Log::error($e->getResponseBody());
            $xeroCustomer = null;
        }
        if ($xeroCustomer && $xeroCustomer[0]->getContactId() != '00000000-0000-0000-0000-000000000000') {
            foreach ($customer->contacts as $contact) {
                $contactPayload = [
                'xero_id' => $xeroCustomer[0]->getContactId(),
                'legal_entity_id' => $this->legalEntity->id,
                'document_id' => $contact->id,
                'document_type' => Contact::class,
                ];
                $this->xeroStorageService->create($contactPayload);
            }
            $customerPayload = [
            'xero_id' => $xeroCustomer[0]->getContactId(),
            'legal_entity_id' => $this->legalEntity->id,
            'document_id' => $customer->id,
            'document_type' => Customer::class,
            ];
            return $this->xeroStorageService->create($customerPayload);
        }
    }

    public function updateCustomer(Customer $customer, string $companyId, string $customerXeroId)
    {
        try {
            $xeroCustomer = $this->customerService->update($customer, $companyId, $customerXeroId);
        } catch (ApiException $e) {
            Log::error($e->getResponseBody());
            $xeroCustomer = null;
        }

        if ($xeroCustomer && $xeroCustomer[0]->getContactId() != '00000000-0000-0000-0000-000000000000') {
            foreach ($customer->contacts as $contact) {
                $alreadyLinked = $this->xeroStorageService->getXeroLinkOfEntity(
                    $contact->id,
                    $this->legalEntity->id,
                    Contact::class
                );

                if (!$alreadyLinked) {
                      $contactPayload = [
                      'xero_id' => $xeroCustomer[0]->getContactId(),
                      'legal_entity_id' => $this->legalEntity->id,
                      'document_id' => $contact->id,
                      'document_type' => Contact::class,
                      ];
                      $this->xeroStorageService->create($contactPayload);
                }
            }
        }
    }

    public function deleteCustomer($xero_id)
    {
        $this->customerService->delete($xero_id);
    }


    public function createResource(Resource $resource, string $companyId)
    {
        try {
            $xeroResource = $this->resourceService->create($resource, $companyId);
        } catch (ApiException $e) {
            Log::error($e->getResponseBody());
            $xeroResource = null;
        }
        if ($xeroResource && $xeroResource[0]->getContactId() != '00000000-0000-0000-0000-000000000000') {
            $resourcePayload = [
            'xero_id' => $xeroResource[0]->getContactId(),
            'legal_entity_id' => $this->legalEntity->id,
            'document_id' => $resource->id,
            'document_type' => Resource::class,
            ];
            return $this->xeroStorageService->create($resourcePayload);
        }
    }

    public function updateResource(Resource $resource, string $companyId, string $legalEntityId)
    {
        try {
            $this->resourceService->update($resource, $companyId, $legalEntityId);
        } catch (ApiException $e) {
            Log::error($e->getResponseBody());
        }
    }

    public function deleteResource($xero_id, string $companyId)
    {
        $this->resourceService->delete($xero_id);
    }

    public function createPurchaseOrder(PurchaseOrder $purchaseOrder, string $companyId)
    {
        try {
            $xeroPurchaseOrder = $this->purchaseOrderService->create($purchaseOrder, $companyId);
            logger(json_encode($xeroPurchaseOrder));
        } catch (ApiException $e) {
            Log::error($e->getResponseBody());
            $xeroPurchaseOrder = null;
        }
        if ($xeroPurchaseOrder && $xeroPurchaseOrder[0]->getPurchaseOrderId() != '00000000-0000-0000-0000-000000000000') {
            $purchaseOrder->xero_id = $xeroPurchaseOrder[0]->getPurchaseOrderId();
            $purchaseOrder->saveQuietly();
        }
    }

    public function updatePurchaseOrder(PurchaseOrder $purchaseOrder, string $companyId)
    {
        try {
            $xeroPurchaseOrder = $this->purchaseOrderService->update($purchaseOrder, $companyId);
            logger(json_encode($xeroPurchaseOrder));
        } catch (ApiException $e) {
            Log::error($e->getResponseBody());
        }
    }

    public function getInvoices($invoice_id)
    {
        return $this->invoiceService->getInvoices($invoice_id);
    }

    public function getCreditNotes()
    {
        return $this->creditNoteService->getCreditNotes();
    }

    public function getTaxRates(): array
    {
        return $this->taxRateService->getTaxRatesFromXero();
    }
}
