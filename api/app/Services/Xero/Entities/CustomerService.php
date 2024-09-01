<?php


namespace App\Services\Xero\Entities;

use App\Enums\Country;
use App\Enums\CurrencyCode;
use App\Enums\CustomerStatus;
use App\Models\Company;
use App\Models\Customer;
use Tenancy\Facades\Tenancy;
use XeroAPI\XeroPHP\Models\Accounting\Address;
use \XeroAPI\XeroPHP\Models\Accounting\Contact;
use \XeroAPI\XeroPHP\Api\AccountingApi;
use XeroAPI\XeroPHP\Models\Accounting\Contacts;
use \XeroAPI\XeroPHP\Models\Accounting\Phone;


class CustomerService extends BaseEntity
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

    public function create(Customer $customer, string $companyId)
    {
        Tenancy::setTenant(Company::find($companyId));

        $contacts      = $this->getContacts($customer);
        $addresses     = $this->getAddresses($customer);
        $currency_code = $customer->currency_code ? CurrencyCode::make($customer->currency_code)->getName() : null;
        $status = Contact::CONTACT_STATUS_ACTIVE;

        $arrCustomer = [];

        $this->accounting->setName($customer->name.$this->getRandNum() ?? null)
          ->setContactStatus($status)
          ->setName($customer->name ?? null)
          ->setFirstName($customer->primaryContact ? $customer->primaryContact->first_name : null)
          ->setLastName($customer->primaryContact ? $customer->primaryContact->last_name : null)
          ->setEmailAddress($customer->email ?? null)
          ->setTaxNumber($customer->tax_number ?? null)
          ->setWebsite($customer->website ?? null)
          ->setDefaultCurrency($currency_code)
          ->setAddresses($addresses)
          ->setIsCustomer(true)
          ->setPhones([$this->getPhone($customer)])
          ->setEmailAddress($customer->email ?? null)
          ->setContactPersons($contacts);

        array_push($arrCustomer, $this->accounting);
        $customerObj = new Contacts();
        $customerObj->setContacts($arrCustomer);

        return $this->apiInstance->createContacts($this->tenant_id, $customerObj, true);
    }

    public function update(Customer $customer, string $companyId, string $customerXeroId)
    {
        Tenancy::setTenant(Company::find($companyId));

        $contacts      = $this->getContacts($customer);
        $addresses     = $this->getAddresses($customer);
        $currency_code = $customer->currency_code ? CurrencyCode::make($customer->currency_code)->getName() : null;
        $status = Contact::CONTACT_STATUS_ACTIVE;

        $arrCustomer = [];

        $this->accounting->setName($customer->name.$this->getRandNum() ?? null)
          ->setContactStatus($status)
          ->setName($customer->name ?? null)
          ->setFirstName($customer->primaryContact ? $customer->primaryContact->first_name : null)
          ->setLastName($customer->primaryContact ? $customer->primaryContact->last_name : null)
          ->setEmailAddress($customer->email ?? null)
          ->setTaxNumber($customer->tax_number ?? null)
          ->setWebsite($customer->website ?? null)
          ->setDefaultCurrency($currency_code)
          ->setAddresses($addresses)
          ->setIsCustomer(true)
          ->setPhones([$this->getPhone($customer)])
          ->setEmailAddress($customer->email ?? null)
          ->setContactPersons($contacts);

        array_push($arrCustomer, $this->accounting);
        $customerObj = new Contacts();
        $customerObj->setContacts($arrCustomer);

        return $this->apiInstance->updateContact($this->tenant_id, $customerXeroId, $customerObj);
    }

    public function delete($xero_id)
    {
        $this->accounting->setContactStatus(Contact::CONTACT_STATUS_ARCHIVED);
        return $this->apiInstance->updateContact($this->tenant_id, $xero_id, $this->accounting);
    }

    public function getContacts(Customer $customer)
    {
        $contactPerson = [];
        $contacts = $customer->contacts()->get();
        foreach ($contacts as $contact) {
            $contactPerson[] = ['FirstName' => $contact->first_name,'LastName' => $contact->last_name,'EmailAddress' => $contact->email];
        }
        return $contactPerson ?? [];
    }

    public function getAddresses(Customer $customer)
    {
        $address = $customer->billing_address()->first();
        $operationalAddress = $customer->operational_address()->first();

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
          ],
          [
              'AddressType' => Address::ADDRESS_TYPE_STREET,
              'AddressLine1' => $operationalAddress->addressline_1,
              'AddressLine2' => $operationalAddress->addressline_2,
              'City' => $operationalAddress->city,
              'Region' => $operationalAddress->region,
              'PostalCode' => $operationalAddress->postal_code ,
              'Country' => Country::make($operationalAddress->country)->getName()
          ]
        ];
    }

    public function getPhone(Customer $customer)
    {
        $phone = new Phone();
        $phone->setPhoneNumber($customer->phone_number)->setPhoneType('DEFAULT');
        return $phone;
    }
}
