<?php

namespace App\Models;

use App\Http\Resources\Comment\CommentResource;
use App\Traits\Models\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;

class Comment extends Model
{
    use uuids;
    use OnTenant;

    protected $fillable = [
        'entity_id',
        'entity_type',
        'created_by',
        'content',
    ];

    protected $resourceClass = CommentResource::class;

    public function entity()
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    public static function getResource()
    {
        return (new static)->resourceClass;
    }

    public function saveQuietly(array $options = [])
    {
        return static::withoutEvents(function () use ($options) {
            return $this->save($options);
        });
    }

    /**
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
