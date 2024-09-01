<?php

namespace App\DTO\GlobalTaxRates;

use Illuminate\Support\Facades\Date;
use Spatie\DataTransferObject\DataTransferObject;

class GlobalTaxRateDTO extends DataTransferObject
{
    /** @var int|float */
    public $tax_rate;

    /** @var string */
    public string $start_date;

    /** @var string|null */
    public ?string $end_date;

    /** @var string|null */
    public ?string $xero_sales_tax_type;

    /** @var string|null */
    public ?string $xero_purchase_tax_type;

    /** @var string|null */
    public ?string $legal_entity_id;
}
