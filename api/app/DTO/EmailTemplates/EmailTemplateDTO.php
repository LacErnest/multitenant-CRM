<?php

namespace App\DTO\EmailTemplates;

use Spatie\DataTransferObject\DataTransferObject;

class EmailTemplateDTO extends DataTransferObject
{
    public ?string $title;

    public ?array $cc_addresses;

    public ?string $html_content;

    public ?string $design;

    public ?string $sender_id;

    public ?string $design_template_id;

    public ?array $reminder_types;

    public ?array $reminder_values;

    public ?array $reminder_templates;

    public ?array $reminder_ids;
}
