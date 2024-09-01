<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CommentRepositoryInterface;
use App\Models\Comment;

class EloquentCommentRepository extends EloquentRepository implements CommentRepositoryInterface
{
    public const CURRENT_MODEL = Comment::class;
}
