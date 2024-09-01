<?php


namespace App\Services;

use App\Http\Requests\Customer\CustomerCreateRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Repositories\CustomerRepository;
use Illuminate\Http\Request;

class CustomerService
{
    protected CustomerRepository $customer_repository;

    public function __construct(CustomerRepository $customer_repository)
    {
        $this->customer_repository = $customer_repository;
    }

    public function get($customer_id)
    {
        return $this->customer_repository->get($customer_id);
    }

    public function create($company_id, CustomerCreateRequest $request)
    {
        return $this->customer_repository->create($company_id, $request->allSafe());
    }

    public function update($customer_id, CustomerUpdateRequest $request, $company_id)
    {
        return $this->customer_repository->update($customer_id, $request->allSafe(), $company_id);
    }

    public function suggest($company_id, Request $request, $value)
    {
        return $this->customer_repository->suggest($company_id, $request, $value);
    }
}
