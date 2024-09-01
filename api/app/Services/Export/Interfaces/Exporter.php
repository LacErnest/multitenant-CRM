<?php

namespace App\Services\Export\Interfaces;

use App\Services\Export\Interfaces\CanBeExported;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface Exporter
{
    /**
     * @param CanBeExported $reminder
     * @return Media
     */
    public function export(CanBeExported $reminder): Media;

    public function getVariables(string $target = null): array;
}
