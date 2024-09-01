<?php


namespace App\Services\Xero\Entities;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\GlobalTaxRateRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Services\Xero\XeroService;
use App\Services\XeroEntityStorageService;
use Illuminate\Support\Facades\App;
use Tenancy\Facades\Tenancy;
use \XeroAPI\XeroPHP\Models\Accounting\Quote;
use \XeroAPI\XeroPHP\Models\Accounting\Contact;
use \XeroAPI\XeroPHP\Api\AccountingApi;
use App\Models\Quote as QuoteModel;
use \XeroAPI\XeroPHP\Models\Accounting\Quotes;
use App\Models\Contact as ContactModel;

class QuoteService extends BaseEntity
{

    protected GlobalTaxRateRepositoryInterface $globalTaxRateRepository;
    protected CompanyRepositoryInterface $companyRepository;
    private Quote $accounting;
    private string $tenant_id;
    private AccountingApi $apiInstance;
    private ContactService $contactService;
    private ItemService $itemService;
    private XeroEntityStorageService $xeroStorageService;

    public function __construct(AccountingApi $apiInstance, string $tenant_id)
    {
        $this->accounting     = new Quote;
        $this->tenant_id      = $tenant_id;
        $this->apiInstance    = $apiInstance;
        $this->contactService = new ContactService($this->apiInstance, $this->tenant_id);
        $this->itemService    = new ItemService($this->apiInstance, $this->tenant_id);
        $this->globalTaxRateRepository = App::make(GlobalTaxRateRepositoryInterface::class);
        $this->companyRepository = App::make(CompanyRepositoryInterface::class);
        $this->xeroStorageService = App::make(XeroEntityStorageService::class);
    }

    public function create(QuoteModel $quote, string $companyId)
    {
        $arr_quotes = $this->createQuoteObject($quote, $companyId, 'create');
        $quotes_obj = new Quotes;
        $quotes_obj->setQuotes($arr_quotes);

        return $this->apiInstance->createQuotes($this->tenant_id, $quotes_obj, true);
    }

    public function update(QuoteModel $quote, string $companyId)
    {
        if (!$quote->xero_id) {
            return null;
        }

        $arr_quotes = $this->createQuoteObject($quote, $companyId, 'update');
        $quotes_obj = new Quotes;
        $quotes_obj->setQuotes($arr_quotes);
        return $this->apiInstance->updateQuote($this->tenant_id, $quote->xero_id, $quotes_obj);
    }


    private function setContact($contact_id)
    {
        $contact = new Contact;

        if ($contact_id) {
            $contact->setContactId($contact_id);
        } else {
            $contactXero  = $this->contactService->createEmpty();
            logger(json_encode($contactXero));
            $contact->setContactId($contactXero[0]->getContactId());
        }

        return $contact;
    }

    private function createQuoteObject(QuoteModel $quote, string $companyId, string $action): array
    {
        $company = $this->companyRepository->firstById($companyId);
        Tenancy::setTenant($company);

        if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
            $price = $quote->total_price_usd;
            $vat = $quote->total_vat_usd;
        } else {
            $price = $quote->total_price;
            $vat = $quote->total_vat;
        }

        $totalNoMods = entityPrice(QuoteModel::class, $quote->id, true);
        $taxIsApplicable = checkIfTaxesAreApplicable($quote);
        if ($taxIsApplicable) {
            $taxRate = $this->globalTaxRateRepository->getTaxRateForPeriod($quote->legal_entity_id, $quote->date);
            $taxType = $taxRate->xero_sales_tax_type ?? 'OUTPUT';
        } else {
            $taxType = 'OUTPUT';
        }

        $lineItems = $this->itemService->getLines(
            $quote->items->sortBy('order'),
            $quote->priceModifiers,
            $quote->manual_input,
            $totalNoMods,
            $quote->currency_rate_customer,
            null,
            $taxType
        );
        $contact = $this->xeroStorageService->getXeroLinkOfEntity(
            $quote->project->contact->id,
            $quote->legal_entity_id,
            ContactModel::class
        );

        if (!$contact) {
            $customer = $this->xeroStorageService->getXeroLinkOfEntity(
                $quote->project->contact->customer->id,
                $quote->legal_entity_id,
                Customer::class
            );
            $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
            $legalEntity = $legalEntityRepository->firstById($quote->legal_entity_id);
            $xeroService = App::makeWith(XeroService::class, ['legalEntity' => $legalEntity]);
            if (!$customer) {
                  $customer = $xeroService->createCustomer($quote->project->contact->customer, $company->id);
            } else {
                $xeroService->updateCustomer($quote->project->contact->customer, $company->id, $customer->xero_id);
            }
            $contact   = $this->setContact($customer->xero_id ?? null);
        } else {
            $contact   = $this->setContact($contact->xero_id ?? null);
        }

        $status = QuoteStatus::make($quote->status)->getName();

        if (QuoteStatus::isOrdered($quote->status)) {
            $status = QuoteStatus::sent()->getName();
        }

        if (QuoteStatus::isCancelled($quote->status)) {
            $status = 'DELETED';
        }

        $arr_quotes = [];
        $this->accounting
          ->setReference($quote->reference)
          ->setQuoteNumber($quote->number)
          ->setExpiryDate($quote->expiry_date)
          ->setDate($quote->date)
          ->setCurrencyCode(CurrencyCode::make($quote->currency_code)->getName())
          ->setTotal($quote->manual_input ? decFormat($quote->manual_price) : decFormat(ceiling($price * $quote->currency_rate_customer, 2)))
          ->setTotalTax($quote->manual_input ? decFormat($quote->manual_vat) : decFormat($vat * $quote->currency_rate_customer))
          ->setCurrencyRate($quote->currency_rate_customer)
          ->setContact($contact)
          ->setLineItems($lineItems);

        if ($action == 'create') {
            $this->accounting
            ->setStatus($status);
        } else {
            if (!QuoteStatus::isDraft($status)) {
                $this->accounting->setStatus($status);
            }
        }

        array_push($arr_quotes, $this->accounting);

        return $arr_quotes;
    }
}
