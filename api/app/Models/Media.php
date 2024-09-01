<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends BaseMedia
{
    use OnTenant;

    public static function boot()
    {
        config()->set('media-library.media_model', self::class);
        parent::boot();
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
