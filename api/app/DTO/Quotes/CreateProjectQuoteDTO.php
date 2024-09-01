<?php

namespace App\DTO\Quotes;

use Spatie\DataTransferObject\DataTransferObject;

class CreateProjectQuoteDTO extends DataTransferObject
{
    public ?string $contact_id;

    public string $date;

    public string $expiry_date;

    public ?string $reference;

    public int $currency_code;

    public bool $manual_input;

    public ?int $down_payment;

    public ?int $down_payment_type;

    public string $legal_entity_id;

    public ?int $vat_status;

    /** @var int|float|null */
    public $vat_percentage;

    /** @var int|bool */
    public $master;
}
