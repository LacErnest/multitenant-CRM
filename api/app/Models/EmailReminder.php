<?php

namespace App\Models;

use App\Enums\NotificationReminderType;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class EmailReminder extends Model
{
    use Uuids, OnTenant;

    protected $fillable = [
        'type',
        'value',
        'design_template_id'
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function designTemplate(): BelongsTo
    {
        return $this->belongsTo(DesignTemplate::class);
    }

    /**
     * Scope a query to have due in type.
     */
    public function scopeDueIn(Builder $query): Builder
    {
        return $query->where('type', NotificationReminderType::due_in()->getIndex());
    }

    /**
     * Scope a query to have overdue by type.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('type', NotificationReminderType::overdue_by()->getIndex());
    }
}
