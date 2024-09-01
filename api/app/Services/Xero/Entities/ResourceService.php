<?php


namespace App\Services\Xero\Entities;

use App\Enums\Country;
use App\Enums\CurrencyCode;
use App\Enums\ResourceStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Resource as ResourceModel;
use App\Services\XeroEntityStorageService;
use Illuminate\Support\Facades\App;
use Tenancy\Facades\Tenancy;
use XeroAPI\XeroPHP\Models\Accounting\Address;
use \XeroAPI\XeroPHP\Models\Accounting\Contact;
use \XeroAPI\XeroPHP\Api\AccountingApi;
use \XeroAPI\XeroPHP\Models\Accounting\Phone;

class ResourceService extends BaseEntity
{
    private Contact $accounting;
    private string $tenant_id;
    private AccountingApi $apiInstance;

    public function __construct(AccountingApi $apiInstance, string $tenant_id)
    {
        $this->accounting     = new Contact;
        $this->tenant_id      = $tenant_id;
        $this->apiInstance    = $apiInstance;
    }

    public function create(ResourceModel $resource, string $companyId)
    {
        Tenancy::setTenant(Company::find($companyId));
        $addresses     = $this->getAddresses($resource);
        $currency_code = $resource->default_currency ? CurrencyCode::make($resource->default_currency)->getName() : null;
        $status = $resource->status ? ResourceStatus::make($resource->status)->getName() : null;

        $this->accounting->setName($resource->name)
          ->setContactStatus($status)
          ->setFirstName($resource->first_name ?? null)
          ->setLastName($resource->last_name ?? null)
          ->setEmailAddress($resource->email ?? null)
          ->setTaxNumber($resource->tax_number ?? null)
          ->setDefaultCurrency($currency_code)
          ->setAddresses($addresses)
          ->setIsSupplier(true)
          ->setPhones([$this->getPhone($resource)])
          ->setEmailAddress($resource->email ?? null);
        return $this->apiInstance->createContacts($this->tenant_id, $this->accounting);
    }

    public function update(ResourceModel $resource, string $companyId, string $legalEntityId)
    {
        Tenancy::setTenant(Company::find($companyId));
        $xeroStorageService = App::make(XeroEntityStorageService::class);
        $xeroLinkResource = $xeroStorageService->getXeroLinkOfEntity($resource->id, $legalEntityId, ResourceModel::class);

        if ($xeroLinkResource) {
            $addresses     = $this->getAddresses($resource);
            $currency_code = $resource->currency_code ? CurrencyCode::make($resource->currency_code)->getName() : null;
            $status = $resource->status ? ResourceStatus::make($resource->status)->getName() : null;

            $this->accounting->setName($resource->name)
            ->setContactStatus($status)
            ->setFirstName($resource->first_name ?? null)
            ->setLastName($resource->last_name ?? null)
            ->setEmailAddress($resource->email ?? null)
            ->setTaxNumber($resource->tax_number ?? null)
            ->setWebsite($resource->website ?? null)
            ->setDefaultCurrency($currency_code)
            ->setAddresses($addresses)
            ->setIsSupplier(true)
            ->setPhones([$this->getPhone($resource)])
            ->setEmailAddress($resource->email ?? null);
            return $this->apiInstance->updateContact($this->tenant_id, $xeroLinkResource->xero_id, $this->accounting);
        }
    }

    public function delete($xero_id)
    {
        $this->accounting->setContactStatus(Contact::CONTACT_STATUS_ARCHIVED);
        return $this->apiInstance->updateContact($this->tenant_id, $xero_id, $this->accounting);
    }


    public function getAddresses(ResourceModel $resource)
    {
        $address = $resource->address()->first();

        if ($address === null) {
            return [];
        }

        return [
          [
              'AddressType' => Address::ADDRESS_TYPE_POBOX,
              'AddressLine1' => $address->addressline_1,
              'AddressLine2' => $address->addressline_2,
              'City' => $address->city,
              'Region' => $address->region,
              'PostalCode' => $address->postal_code ,
              'Country' => Country::make($address->country)->getName()
          ]
        ];
    }

    public function getPhone(ResourceModel $resource)
    {
        $phone = new Phone();
        $phone->setPhoneNumber($resource->phone_number)->setPhoneType('DEFAULT');
        return $phone;
    }
}
