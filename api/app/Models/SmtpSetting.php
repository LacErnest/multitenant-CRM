<?php

namespace App\Models;

use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class SmtpSetting extends Model
{
    use Uuids, OnTenant;
    protected $fillable = [
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'sender_email',
        'sender_name',
        'default'
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class);
    }

    /**
     * Scope a query to have default smtp settings.
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('default', true);
    }
}
