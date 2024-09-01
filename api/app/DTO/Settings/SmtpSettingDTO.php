<?php

namespace App\DTO\Settings;

use Spatie\DataTransferObject\DataTransferObject;

class SmtpSettingDTO extends DataTransferObject
{
    public ?string $smtp_port;

    public ?string $smtp_host;

    public ?string $smtp_encryption;

    public ?string $smtp_username;

    public ?string $smtp_password;

    public ?string $sender_email;

    public ?string $sender_name;
}
