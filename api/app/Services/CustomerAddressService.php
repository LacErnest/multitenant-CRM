<?php

namespace App\Services;

use App\Contracts\Repositories\CustomerAddressRepositoryInterface;
use App\DTO\Addresses\AddressDTO;
use App\Models\CustomerAddress;

class CustomerAddressService
{
    protected CustomerAddressRepositoryInterface $customerAddressRepository;

    public function __construct(CustomerAddressRepositoryInterface $customerAddressRepository)
    {
        $this->customerAddressRepository = $customerAddressRepository;
    }

    public function create(AddressDTO $addressDTO): CustomerAddress
    {
        return $this->customerAddressRepository->create($addressDTO->toArray());
    }

    public function update(string $addressId, AddressDTO $addressDTO): CustomerAddress
    {
        $this->customerAddressRepository->update($addressId, $addressDTO->toArray());

        return $this->customerAddressRepository->firstById($addressId);
    }
}
