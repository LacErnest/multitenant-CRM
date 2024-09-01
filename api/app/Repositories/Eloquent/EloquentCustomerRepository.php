<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class EloquentCustomerRepository extends EloquentRepository implements CustomerRepositoryInterface
{
    public const CURRENT_MODEL = Customer::class;

    protected Customer $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function getCompanyCustomersWithXero(string $companyId)
    {
        return $this->customer->with('contacts')->where('company_id', $companyId)->whereNotNull('xero_id')->get();
    }
}
