<?php

namespace App\Services\Export\Interfaces;

use Spatie\MediaLibrary\HasMedia;

interface CanBeExported extends HasMedia
{
    public function getTemplatePath($template_id);

    public function getExportMediaCollection(): string;
}
