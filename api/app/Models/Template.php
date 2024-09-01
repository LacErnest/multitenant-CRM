<?php

namespace App\Models;

use App\Enums\TemplateType;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class Template extends Model implements HasMedia
{
    use uuids;
    use OnTenant;
    use InteractsWithMedia;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];

    public static function boot()
    {
        config()->set('media-library.media_model', Media::class);
        parent::boot();
    }

    public function media(): MorphMany
    {
        return $this->morphMany('App\Models\Media', 'model');
    }

    public function registerMediaCollections(): void
    {
        foreach (TemplateType::getValues() as $value) {
            $this->addMediaCollection('templates_'.$value)->useDisk('templates')->singleFile();
        }
    }
}
