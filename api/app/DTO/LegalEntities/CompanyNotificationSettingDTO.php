<?php

namespace App\DTO\LegalEntities;

use Spatie\DataTransferObject\DataTransferObject;

class CompanyNotificationSettingDTO extends DataTransferObject
{

    public string $from_address;

    public string $from_name;

    public string $invoice_submitted_body;

    public array $cc_addresses = [];
}
