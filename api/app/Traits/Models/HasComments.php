<?php

namespace App\Traits\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Comment;

trait HasComments
{
    /**
     * Define the "comments" relationship.
     *
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'entity')->orderByDesc('created_at');
    }
}
