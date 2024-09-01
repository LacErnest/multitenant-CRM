<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class EmailTemplate extends Model
{
    use Uuids;
    use OnTenant;

    protected $fillable = [
        'title',
        'cc_addresses',
        'html_file',
        'design_template_id',
        'default',
    ];

    protected $casts = [
        'cc_addresses'=> 'array',
    ];

    public function smtpSetting(): BelongsTo
    {
        return $this->belongsTo(SmtpSetting::class);
    }

    public function reminders(): MorphMany
    {
        return $this->morphMany(EmailReminder::class, 'entity')->orderBy('created_at', 'ASC');
    }

    public function designTemplate(): BelongsTo
    {
        return $this->belongsTo(DesignTemplate::class);
    }

    /**
     * Scope a query to have default smtp settings.
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('default', true);
    }
}
