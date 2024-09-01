<?php

namespace App\Models;

use App\Enums\TemplateType;
use App\Traits\Models\Uuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Class LegalEntitySetting
 * Model used to set numbers on documents
 *
 * @property string quote_number
 * @property string quote_number_format
 * @property string order_number
 * @property string order_number_format
 * @property string invoice_number
 * @property string invoice_number_format
 * @property string purchase_order_number
 * @property string purchase_order_number_format
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class LegalEntitySetting extends Model implements HasMedia
{
    use Uuids,
        InteractsWithMedia;

    protected $connection = 'mysql';

    protected $fillable = [
        'quote_number',
        'quote_number_format',
        'order_number',
        'order_number_format',
        'invoice_number',
        'invoice_number_format',
        'purchase_order_number',
        'purchase_order_number_format',
        'resource_invoice_number',
        'resource_invoice_number_format',
        'invoice_payment_number',
        'invoice_payment_number_format',
        'enable_submited_invoice_notification',
        'notification_footer',
        'notification_contacts'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public static function boot()
    {
        config()->set('media-library.media_model', GlobalMedia::class);
        parent::boot();
    }

    public function registerMediaCollections(): void
    {
        $templates = [
            TemplateType::customer()->getValue(),
            TemplateType::contractor()->getValue(),
            TemplateType::NDA()->getValue(),
            TemplateType::freelancer()->getValue(),
            TemplateType::employee()->getValue(),
        ];

        foreach ($templates as $template) {
            $this->addMediaCollection('templates_'.$template)->useDisk('templates.legal_entities')->singleFile();
        }
    }

    /** RELATIONS */
    /**
     * @return HasOne
     */
    public function legalEntity(): HasOne
    {
        return $this->hasOne(LegalEntity::class, 'legal_entity_setting_id');
    }

    /**
     * @return MorphMany
     */
    public function media(): MorphMany
    {
        return $this->morphMany(GlobalMedia::class, 'model');
    }
}
