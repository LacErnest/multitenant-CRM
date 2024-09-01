<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GlobalMedia extends BaseMedia
{
    protected $connection = 'mysql';

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
