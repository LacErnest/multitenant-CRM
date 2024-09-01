<?php

namespace App\Services\Imports;

use App\Enums\ContactGenderTypes;
use App\Enums\Country;
use App\Enums\CurrencyCode;
use App\Enums\CustomerStatus;
use App\Enums\IndustryType;
use App\Enums\UserRole;
use App\Http\Requests\Contact\ContactCreateRequest;
use App\Http\Requests\Contact\ContactImportUpdateRequest;
use App\Http\Requests\Customer\CustomerAddressRequest;
use App\Http\Requests\Customer\CustomerCreateRequest;
use App\Http\Requests\Customer\CustomerImportCreateRequest;
use App\Http\Requests\Customer\CustomerImportUpdateRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Repositories\CustomerRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Symfony\Component\HttpFoundation\Request;

class CustomerImportService extends BaseImport implements ToCollection, WithStartRow, WithChunkReading
{
    protected string $mediaCollection = 'imports_customers';
    protected int $startRow = 2;
    protected $limit = 2;

    public function __construct()
    {
        parent::__construct();
    }

    public function startRow(): int
    {
        return $this->startRow;
    }

    public function chunkSize(): int
    {
        return 500;
    }


    public function collection(Collection $rows)
    {
        $company = Company::find(getTenantWithConnection());

        foreach ($rows as $row) {
            $contacts = $this->getContactColumnsValue([
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'department',
            'title',
            'gender',
            'linked_in_profile'
            ], $row);

            $customer = [
              'xero_id'          => $this->getColumnValue('xero_id', $row),
              'status'           => $this->getColumnValue('status', $row),
              'name'             => $this->getColumnValue('name', $row),
              'description'      => $this->getColumnValue('description', $row),
              'industry'         => $this->getColumnValue('industry', $row),
              'email'            => $this->getColumnValue('email', $row),
              'tax_number'       => (string)$this->getColumnValue('tax_number', $row),
              'default_currency' => $this->getColumnValue('default_currency', $row),
              'website'          => $this->getColumnValue('website', $row),
              'phone_number'     => (string)$this->getColumnValue('phone_number', $row),
              'sales_person_id'  => $this->getColumnValue('sales_person', $row),
              'legacy_customer'  => $this->getColumnValue('legacy_customer', $row),
            ];

            $address = [
            'addressline_1' => $this->getColumnValue('addressline_1', $row),
            'addressline_2' => $this->getColumnValue('addressline_2', $row),
            'city'          => $this->getColumnValue('city', $row),
            'region'        => $this->getColumnValue('region', $row),
            'postal_code'   => (string)$this->getColumnValue('postal_code', $row),
            'country'       => $this->getColumnValue('country', $row),
            ];

            if (Arr::exists($customer, 'industry') && $customer['industry']) {
                $customer['industry'] = transformToEnum($customer['industry'], IndustryType::class);
            }
            if (Arr::exists($address, 'country') && $address['country']) {
                $address['country'] = transformToEnum($address['country'], Country::class);
            }

            $existingCustomer = Customer::where('name', $customer['name'])->first();
            if ($existingCustomer) {
                $customerAddress = $existingCustomer->billing_address;

                if (Validator::make($customer, (new CustomerImportUpdateRequest($company->id, $existingCustomer->id))->rules())->fails()) {
                    $messages = Validator::make($customer, (new CustomerImportUpdateRequest($company->id, $existingCustomer->id))
                    ->rules())->getMessageBag()->getMessages();
                    $this->setNotValidFileRows($row->toArray(), $messages);
                    continue;
                }

                if (Validator::make($address, (new CustomerAddressRequest())->rules())->fails()) {
                    $messages = Validator::make($address, (new CustomerAddressRequest())->rules())->getMessageBag()->getMessages();
                    $this->setNotValidFileRows($row->toArray(), $messages);
                    continue;
                }

                if ($customer['legacy_customer']) {
                    if (!$existingCustomer->legacyCompanies()->where('company_id', $company->id)->exists()) {
                        $existingCustomer->legacyCompanies()->attach($company->id);
                    }
                }

                $address = array_filter($address, function ($key) use ($customerAddress) {
                    return blank($customerAddress->getAttribute($key));
                }, ARRAY_FILTER_USE_KEY);

                $customer = array_filter($customer, function ($key) use ($existingCustomer) {
                    return blank($existingCustomer->getAttribute($key));
                }, ARRAY_FILTER_USE_KEY);

                $existingCustomer->billing_address->fill($address)->save();
                $existingCustomer->fill($customer)->save();

                foreach ($contacts as $contact) {
                    $existingContact = Contact::where([['first_name', $contact['first_name']],
                      ['last_name', $contact['last_name']], ['customer_id', $existingCustomer->id]])->first();

                    if ($existingContact) {
                        if (Validator::make($contact, (new ContactImportUpdateRequest($existingContact->id))->rules())->fails()) {
                            $messages = Validator::make($contact, (new ContactImportUpdateRequest($existingContact->id))->rules())->getMessageBag()->getMessages();
                            $this->setNotValidFileRows($row->toArray(), $messages);
                            continue;
                        }

                          $contact = array_filter($contact, function ($key) use ($existingContact) {
                                return blank($existingContact->getAttribute($key));
                          }, ARRAY_FILTER_USE_KEY);
                          $existingContact->fill($contact)->save();
                    } else {
                        if (!in_array('gender', $contact) || $contact['gender'] == null) {
                                $contact['gender'] = 'male';
                        }
                        $contact['customer_id'] = $existingCustomer->id;

                        if (Validator::make($contact, (new ContactCreateRequest())->rules())->fails()) {
                            $messages = Validator::make($contact, (new ContactCreateRequest())->rules())->getMessageBag()->getMessages();
                            logger($messages);
                            $this->setNotValidFileRows($row->toArray(), $messages);
                            continue;
                        }
                        $existingCustomer->contacts()->create($contact);

                        if ($existingCustomer->contacts()->count() == 1) {
                            $existingCustomer->primary_contact_id = $existingContact->contacts()->first()->id;
                            $existingCustomer->save();
                        }
                    }
                }

                $this->importData[] = $existingCustomer;
            } else {
                $customer['company_id'] = $company->id;

                if (Arr::exists($customer, 'status') && $customer['status']) {
                    $customer['status'] = transformToEnum($customer['status'], CustomerStatus::class);
                }
                if (Arr::exists($customer, 'default_currency') && $customer['default_currency']) {
                    $customer['default_currency'] = transformToEnum($customer['default_currency'], CurrencyCode::class);
                }

                if ($customer['default_currency'] == null) {
                    $customer['default_currency'] = $company->currency_code;
                }
                if ($customer['legacy_customer'] == null) {
                    $customer['legacy_customer'] = 1;
                }
                if ($customer['status'] == null) {
                    $customer['status'] = CustomerStatus::active()->getIndex();
                }

                if (Validator::make($customer, (new CustomerImportCreateRequest($company->id))->rules())->fails()) {
                    $messages = Validator::make($customer, (new CustomerImportCreateRequest($company->id))->rules())->getMessageBag()->getMessages();
                    logger($messages);
                    $this->setNotValidFileRows($row->toArray(), $messages);
                    continue;
                }

                if (Validator::make($address, (new CustomerAddressRequest())->rules())->fails()) {
                    $messages = Validator::make($address, (new CustomerAddressRequest())->rules())->getMessageBag()->getMessages();
                    logger($messages);
                    $this->setNotValidFileRows($row->toArray(), $messages);
                    continue;
                }

                if ($newCustomer = Customer::create($customer)) {
                    $address = CustomerAddress::create($address);
                    $newCustomer->billing_address_id = $address->id;

                    if ($newCustomer->legacy_customer) {
                        $newCustomer->legacyCompanies()->attach($company->id);
                    }

                    foreach ($contacts as $contact) {
                        if (!in_array('gender', $contact) || $contact['gender'] == null) {
                            $contact['gender'] = 'male';
                        }
                        $contact['customer_id'] = $newCustomer->id;

                        if (Validator::make($contact, (new ContactCreateRequest())->rules())->fails()) {
                            $messages = Validator::make($contact, (new ContactCreateRequest())->rules())->getMessageBag()->getMessages();
                            $this->setNotValidFileRows($row->toArray(), $messages);
                            continue;
                        }
                        $newCustomer->contacts()->create($contact);
                        if ($newCustomer->contacts()->count() == 1) {
                            $newCustomer->primary_contact_id = $newCustomer->contacts()->first()->id;
                        }
                    }
                    $newCustomer->save();
                }
                $this->importData[] = $newCustomer;
            }
        }
    }

    public function getProperties(): array
    {
        $customers         = (new Customer())->getFillable();
        $contactProperties = (new Contact())->getFillable();
        $addressProperties = (new CustomerAddress())->getFillable();

        $result = array_merge($customers, $addressProperties);

        for ($i = 1; $i <= 5; $i++) {
            foreach ($contactProperties as $contactProperty) {
                $result[] = 'contact_'.$i.'_'.$contactProperty;
            }
        }

        return $result;
    }

    private function getContactColumnsValue(array $columnNames, $row)
    {
        for ($i = 1; $i <= 5; $i++) {
            foreach ($columnNames as $columnName) {
                $originName = 'contact_' . $i . '_' . $columnName;
                if ($value = $this->getColumnValue($originName, $row)) {
                    $contacts[$i][$columnName] = $value;
                }
            }
        }

        return $contacts ?? [];
    }
}
