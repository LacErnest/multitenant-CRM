<?php

namespace App\Models;

use App\Models\Media;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class DesignTemplate extends Model implements HasMedia
{
    use Uuids;
    use OnTenant;
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'subject',
    ];

    public function media(): MorphMany
    {
        return $this->morphMany('App\Models\Media', 'model');
    }

    public static function boot()
    {
        config()->set('media-library.media_model', Media::class);
        parent::boot();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('design_template')->useDisk('templates')->singleFile();
    }

    public function emailTemplates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class);
    }

    public function reminderTemplates(): HasMany
    {
        return $this->hasMany(EmailReminder::class);
    }
}
