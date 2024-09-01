<?php

namespace App\DTO\DesignTemplates;

use Spatie\DataTransferObject\DataTransferObject;

class DesignTemplateDTO extends DataTransferObject
{
    public ?string $title;

    public ?string $subject;

    public ?string $html;

    public ?string $design;
}
