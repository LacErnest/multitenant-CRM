<?php


namespace App\Services\Xero\Entities;

use App\Models\Contact as ContactModel;
use \XeroAPI\XeroPHP\Models\Accounting\Contact;
use \XeroAPI\XeroPHP\Models\Accounting\Contacts;
use \XeroAPI\XeroPHP\Api\AccountingApi;


class ContactService extends BaseEntity
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

    public function createEmpty()
    {
        $this->accounting->setName($this->getRandNum());
        return $this->apiInstance->createContacts($this->tenant_id, $this->accounting);
    }


    public function create(ContactModel $contact)
    {
        $this->accounting->setName($contact->name.$this->getRandNum() ?? null)
          ->setFirstName($contact->first_name ?? null)
          ->setLastName($contact->last_name ?? null)
          ->setPhones([['phone_type' => 'DEFAULT','phone_number' => $contact->phone_number,'phone_area_code' => null,'phone_country_code' => null]])
          ->setEmailAddress($contact->email ?? null);
        return $this->apiInstance->createContacts($this->tenant_id, $this->accounting);
    }

    public function update(ContactModel $contact)
    {
        $this->accounting->setName($contact->name.$this->getRandNum() ?? null)
          ->setFirstName($contact->first_name ?? null)
          ->setLastName($contact->last_name ?? null)
          ->setPhones([['phone_type' => 'DEFAULT','phone_number' => $contact->phone_number,'phone_area_code' => null,'phone_country_code' => null]])
          ->setEmailAddress($contact->email ?? null);
        return $this->apiInstance->updateContact($this->tenant_id, $contact->xero_id, $this->accounting);
    }

    public function delete($xero_id)
    {
        $this->accounting->setContactStatus(Contact::CONTACT_STATUS_ARCHIVED);
        return $this->apiInstance->updateContact($this->tenant_id, $xero_id, $this->accounting);
    }
}
