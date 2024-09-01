<?php

namespace App\DTO\Banks;

use App\DTO\Addresses\AddressDTO;
use Spatie\DataTransferObject\DataTransferObject;

class AmericanBankDTO extends DataTransferObject
{
    public string $name;

    public string $account_number;

    public string $routing_number;

    public string $usa_account_number;

    public string $usa_routing_number;

    public AddressDTO $bank_address;
}
