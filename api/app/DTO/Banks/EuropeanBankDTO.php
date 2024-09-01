<?php

namespace App\DTO\Banks;

use App\DTO\Addresses\AddressDTO;
use Spatie\DataTransferObject\DataTransferObject;

class EuropeanBankDTO extends DataTransferObject
{
    public string $name;

    public string $iban;

    public string $bic;

    public AddressDTO $bank_address;
}
