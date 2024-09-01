<?php

namespace App\Services\Imports;

use App\Enums\Country;
use App\Enums\CurrencyCode;
use App\Enums\EmployeeStatus;
use App\Enums\EmployeeType;
use App\Http\Requests\Employee\EmployeeAddressRequest;
use App\Http\Requests\Employee\EmployeeImportCreateRequest;
use App\Http\Requests\Employee\EmployeeImportUpdateRequest;
use App\Models\Address;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Facades\Validator;

class EmployeeImportService extends BaseImport implements ToCollection, WithStartRow
{
    protected string $mediaCollection = 'imports_employees';
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
            $employee = [
            'first_name'            => $this->getColumnValue('first_name', $row),
            'last_name'             => $this->getColumnValue('last_name', $row),
            'email'                 => $this->getColumnValue('email', $row),
            'type'                  => $this->getColumnValue('type', $row),
            'status'                => $this->getColumnValue('status', $row),
            'salary'                => $this->getColumnValue('salary', $row),
            'working_hours'         => $this->getColumnValue('working_hours', $row),
            'phone_number'          => (string)$this->getColumnValue('phone_number', $row),
            'started_at'            => $this->getColumnValue('started_at', $row),
            'linked_in_profile'     => $this->getColumnValue('linked_in_profile', $row),
            'facebook_profile'      => $this->getColumnValue('facebook_profile', $row),
            'role'                  => $this->getColumnValue('role', $row),
            'default_currency'      => $this->getColumnValue('default_currency', $row),
            'legal_entity_id'       => $this->getColumnValue('legal_entity_id', $row),
            'is_pm'                 => $this->getColumnValue('is_pm', $row),
            ];

            $address = [
            'addressline_1' => $this->getColumnValue('addressline_1', $row),
            'addressline_2' => $this->getColumnValue('addressline_2', $row),
            'city'          => $this->getColumnValue('city', $row),
            'region'        => $this->getColumnValue('region', $row),
            'postal_code'   => (string)$this->getColumnValue('postal_code', $row),
            'country'       => $this->getColumnValue('country', $row),
            ];

            if (isset($employee['address_id'])) {
                unset($employee['address_id']);
            }
            if (Arr::exists($address, 'country') && $address['country']) {
                $address['country'] = transformToEnum($address['country'], Country::class);
            }

            $existingEmployee = Employee::where([['first_name', $employee['first_name']], ['last_name', $employee['last_name']]])->first();

            if ($existingEmployee) {
                $employeeAddress = $existingEmployee->address;

                if (Validator::make($employee, (new EmployeeImportUpdateRequest($existingEmployee->id))->rules())->fails()) {
                    $messages = Validator::make($employee, (new EmployeeImportUpdateRequest($existingEmployee->id))->rules())->getMessageBag()->getMessages();
                    $this->setNotValidFileRows($row->toArray(), $messages);
                    continue;
                }

                if (Validator::make($address, (new EmployeeAddressRequest())->rules())->fails()) {
                    $messages = Validator::make($address, (new EmployeeAddressRequest())->rules())->getMessageBag()->getMessages();
                    $this->setNotValidFileRows($row->toArray(), $messages);
                    continue;
                }


                $address = array_filter($address, function ($key) use ($employeeAddress) {
                    return blank($employeeAddress->getAttribute($key));
                }, ARRAY_FILTER_USE_KEY);

                $employee = array_filter($employee, function ($key) use ($existingEmployee) {
                    return blank($existingEmployee->getAttribute($key));
                }, ARRAY_FILTER_USE_KEY);

                $existingEmployee->address->fill($address)->save();
                $existingEmployee->fill($employee)->save();
                $this->importData[] = $existingEmployee;
            } else {
                if (Arr::exists($employee, 'type') && $employee['type']) {
                    $employee['type'] = transformToEnum($employee['type'], EmployeeType::class);
                }
                if (Arr::exists($employee, 'status') && $employee['status']) {
                    $employee['status'] = transformToEnum($employee['status'], EmployeeStatus::class);
                }
                if (Arr::exists($employee, 'default_currency') && $employee['default_currency']) {
                    $employee['default_currency'] = transformToEnum($employee['default_currency'], CurrencyCode::class);
                }

                if ($employee['default_currency'] == null) {
                    $employee['default_currency'] = $company->currency_code;
                }

                if ($employee['legal_entity_id'] == null) {
                    $employee['legal_entity_id'] = $company->defaultLegalEntity()->id;
                }

                if ($employee['type'] == null) {
                    $employee['type'] = EmployeeType::employee()->getIndex();
                }

                if ($employee['status'] == null) {
                    $employee['status'] = EmployeeStatus::active()->getIndex();
                }

                if ($employee['is_pm'] == null) {
                    $employee['is_pm'] = 0;
                }

                if (Validator::make($employee, (new EmployeeImportCreateRequest($company->id))->rules())->fails()) {
                    $messages = Validator::make($employee, (new EmployeeImportCreateRequest($company->id))->rules())->getMessageBag()->getMessages();
                    $this->setNotValidFileRows($row->toArray(), $messages);
                    continue;
                }

                if (Validator::make($address, (new EmployeeAddressRequest())->rules())->fails()) {
                    $messages = Validator::make($address, (new EmployeeAddressRequest())->rules())->getMessageBag()->getMessages();
                    $this->setNotValidFileRows($row->toArray(), $messages);
                    continue;
                }

                if ($customer = Employee::create($employee)) {
                    $address = Address::create($address);
                    $address->employees()->save($customer);
                }
                $this->importData[] = $customer;
            }
        }
    }

    public function getProperties(): array
    {
        $employeeProperties = (new Employee())->getFillable();
        $addressProperties  = (new Address())->getFillable();
        return array_merge($employeeProperties, $addressProperties);
    }
}
