<?php

namespace App\DTO\Invoices;

use Spatie\DataTransferObject\DataTransferObject;

class CreateInvoiceDTO extends DataTransferObject
{
    public string $date;

    public string $due_date;

    public ?string $reference;

    public int $currency_code;

    public bool $manual_input;

    public string $legal_entity_id;

    public ?int $vat_status;

    public ?int $down_payment;

    public ?int $down_payment_status;

    /** @var int|float|null */
    public $vat_percentage;

    /** @var int|bool|null */
    public $master;

    public bool $eligible_for_earnout;
}
