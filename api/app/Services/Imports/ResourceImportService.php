<?php

namespace App\Services\Imports;

use App\Enums\Country;
use App\Enums\CurrencyCode;
use App\Enums\ResourceStatus;
use App\Enums\ResourceType;
use App\Http\Requests\Resource\ResourceAddressRequest;
use App\Http\Requests\Resource\ResourceCreateRequest;
use App\Http\Requests\Resource\ResourceImportCreateRequest;
use App\Http\Requests\Resource\ResourceImportUpdateRequest;
use App\Http\Requests\Resource\ResourceUpdateRequest;
use App\Models\Address;
use App\Models\Company;
use App\Models\Resource;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\Validator;

class ResourceImportService extends BaseImport implements ToCollection, WithStartRow
{
    protected string $mediaCollection = 'imports_resources';
    protected int $startRow = 2;

    public function __construct()
    {
        parent::__construct();
    }

    public function startRow(): int
    {
        return $this->startRow;
    }

    public function collection(Collection $rows)
    {
        $company = Company::find(getTenantWithConnection());

        foreach ($rows as $row) {
            $resource = [
            'name'             => $this->getColumnValue('name', $row),
            'first_name'       => $this->getColumnValue('first_name', $row),
            'last_name'        => $this->getColumnValue('last_name', $row),
            'email'            => $this->getColumnValue('email', $row),
            'type'             => $this->getColumnValue('type', $row),
            'status'           => $this->getColumnValue('status', $row),
            'tax_number'       => (string)$this->getColumnValue('tax_number', $row),
            'default_currency' => $this->getColumnValue('default_currency', $row),
            'phone_number'     => (string)$this->getColumnValue('phone_number', $row),
            'job_title'        => $this->getColumnValue('job_title', $row),
            'hourly_rate'      => $this->getColumnValue('hourly_rate', $row),
            'daily_rate'       => $this->getColumnValue('daily_rate', $row),
            'legal_entity_id'  => $this->getColumnValue('legal_entity_id', $row),
            ];

            $address = [
            'addressline_1' => $this->getColumnValue('addressline_1', $row),
            'addressline_2' => $this->getColumnValue('addressline_2', $row),
            'city'          => $this->getColumnValue('city', $row),
            'region'        => $this->getColumnValue('region', $row),
            'postal_code'   => (string)$this->getColumnValue('postal_code', $row),
            'country'       => $this->getColumnValue('country', $row),
            ];

            if (isset($resource['address_id'])) {
                unset($resource['address_id']);
            }
            if (Arr::exists($address, 'country') && $address['country']) {
                $address['country'] = transformToEnum($address['country'], Country::class);
            }

            $existingResource = Resource::where([['first_name', $resource['first_name']], ['last_name', $resource['last_name']]])->first();

            if ($existingResource) {
                $resourceAddress = $existingResource->address;

                if (Validator::make($resource, (new ResourceImportUpdateRequest($existingResource->id))->rules())->fails()) {
                    $messages = Validator::make($resource, (new ResourceImportUpdateRequest($existingResource->id))->rules())->getMessageBag()->getMessages();
                    $this->setNotValidFileRows($row->toArray(), $messages);
                    continue;
                }

                if (Validator::make($address, (new ResourceAddressRequest())->rules())->fails()) {
                    $messages = Validator::make($address, (new ResourceAddressRequest())->rules())->getMessageBag()->getMessages();
                    $this->setNotValidFileRows($row->toArray(), $messages);
                    continue;
                }

                $address = array_filter($address, function ($key) use ($resourceAddress) {
                    return blank($resourceAddress->getAttribute($key));
                }, ARRAY_FILTER_USE_KEY);

                $resource = array_filter($resource, function ($key) use ($existingResource) {
                    return blank($existingResource->getAttribute($key));
                }, ARRAY_FILTER_USE_KEY);

                $existingResource->address->fill($address)->save();
                $existingResource->fill($resource)->save();
                $this->importData[] = $existingResource;
            } else {
                if (Arr::exists($resource, 'type') && $resource['type']) {
                    $resource['type'] = transformToEnum($resource['type'], ResourceType::class);
                }
                if (Arr::exists($resource, 'status') && $resource['status']) {
                    $resource['status'] = transformToEnum($resource['status'], ResourceStatus::class);
                }
                if (Arr::exists($resource, 'default_currency') && $resource['default_currency']) {
                    $resource['default_currency'] = transformToEnum($resource['default_currency'], CurrencyCode::class);
                }

                if ($resource['default_currency'] == null) {
                    $resource['default_currency'] = $company->currency_code;
                }

                if ($resource['legal_entity_id'] == null) {
                    $resource['legal_entity_id'] = $company->defaultLegalEntity()->id;
                }

                if ($resource['type'] == null) {
                    $resource['type'] = ResourceType::freelancer()->getIndex();
                }

                if ($resource['status'] == null) {
                    $resource['status'] = ResourceStatus::active()->getIndex();
                }

                if (Validator::make($resource, (new ResourceImportCreateRequest($company->id))->rules())->fails()) {
                    $messages = Validator::make($resource, (new ResourceImportCreateRequest($company->id))->rules())->getMessageBag()->getMessages();
                    $this->setNotValidFileRows($row->toArray(), $messages);
                    continue;
                }

                if (Validator::make($address, (new ResourceAddressRequest())->rules())->fails()) {
                    $messages = Validator::make($address, (new ResourceAddressRequest())->rules())->getMessageBag()->getMessages();
                    $this->setNotValidFileRows($row->toArray(), $messages);
                    continue;
                }

                if ($customer = Resource::create($resource)) {
                    $address = Address::create($address);
                    $address->resources()->save($customer);
                }
                $this->importData[] = $customer;
            }
        }
    }

    public function getProperties(): array
    {
        $resourceProperties = (new Resource())->getFillable();
        $addressProperties  = (new Address())->getFillable();
        return array_merge($resourceProperties, $addressProperties);
    }
}
