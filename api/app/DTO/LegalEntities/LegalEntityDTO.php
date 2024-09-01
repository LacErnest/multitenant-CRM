<?php

namespace App\DTO\LegalEntities;

use App\DTO\Addresses\AddressDTO;
use App\DTO\Banks\AmericanBankDTO;
use App\DTO\Banks\EuropeanBankDTO;
use Spatie\DataTransferObject\DataTransferObject;

class LegalEntityDTO extends DataTransferObject
{
    public string $name;

    public string $vat_number;

    public string $usdc_wallet_address;

    public AddressDTO $legal_entity_address;

    public EuropeanBankDTO $european_bank;

    public ?AmericanBankDTO $american_bank;
}
