<?php

namespace App\DTO\LegalEntities;

use Spatie\DataTransferObject\DataTransferObject;

class LegalEntitySettingDTO extends DataTransferObject
{
    public string $quote_number;

    public string $quote_number_format;

    public string $order_number;

    public string $order_number_format;

    public string $invoice_number;

    public string $invoice_number_format;

    public string $purchase_order_number;

    public string $purchase_order_number_format;

    public ?string $resource_invoice_number;

    public ?string $resource_invoice_number_format;

    public ?string $invoice_payment_number;

    public ?string $invoice_payment_number_format;

    public ?bool $enable_submited_invoice_notification;

    public ?string $notification_footer;

    public ?array $notification_contacts = [];
}
