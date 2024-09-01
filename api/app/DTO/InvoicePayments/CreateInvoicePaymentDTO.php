<?php

namespace App\DTO\InvoicePayments;

use Spatie\DataTransferObject\DataTransferObject;

class CreateInvoicePaymentDTO extends DataTransferObject
{
    public $currency_code;

    public $pay_amount;

    public $pay_date;

    public $pay_full_price;
}
