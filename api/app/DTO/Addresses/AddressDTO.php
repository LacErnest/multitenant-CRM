<?php

namespace App\DTO\Addresses;

use Spatie\DataTransferObject\DataTransferObject;

class AddressDTO extends DataTransferObject
{
    public string $addressline_1;

    public ?string $addressline_2;

    public string $city;

    public ?string $region;

    public string $postal_code;

    public int $country;
}
