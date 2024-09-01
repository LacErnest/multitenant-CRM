<?php

namespace App\DTO\Orders;

use Spatie\DataTransferObject\DataTransferObject;

class CreateOrderDTO extends DataTransferObject
{
    public ?string $project_manager_id;

    public string $date;

    public string $deadline;

    public ?string $reference;

    public int $currency_code;

    public bool $manual_input;

    public string $legal_entity_id;
}
