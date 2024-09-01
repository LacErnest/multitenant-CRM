<?php

namespace App\DTO\PurchaseOrders;

use Spatie\DataTransferObject\DataTransferObject;

class CreatePurchaseOrderDTO extends DataTransferObject
{
    public string $date;

    public string $delivery_date;

    public ?string $reference;

    public int $currency_code;

    public string $resource_id;

    public bool $manual_input;

    public ?string $legal_entity_id;

    public int $payment_terms;

    public ?int $vat_status;

    /** @var int|float|null */
    public $vat_percentage;
}
