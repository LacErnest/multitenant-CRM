<?php

namespace App\DTO\LegalEntities;

use Spatie\DataTransferObject\DataTransferObject;

class LegalEntityNotificationSettingDTO extends DataTransferObject
{

    public ?bool $enable_submited_invoice_notification;

    public ?string $notification_footer;

    public ?array $notification_contacts = [];

    public function __construct(array $data)
    {
        parent::__construct(
            [
              'enable_submited_invoice_notification' => convertToBool($data['enable_submited_invoice_notification']),
              'notification_footer' => $data['notification_footer'] ?? null,
              'notification_contacts' => $data['notification_contacts'] ?? [],
            ]
        );
    }
}
