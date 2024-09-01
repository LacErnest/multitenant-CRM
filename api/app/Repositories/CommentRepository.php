<?php

namespace App\Repositories;

use App\Models\Invoice;
use App\Models\Comment;


/**
 * Class CommentRepository
 *
 * @deprecated
 */
class CommentRepository
{
    protected Comment $comment;
    /**
     * @var Comment
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    public function create(string $entity_id, array $attributes)
    {
        $invoice = Invoice::with('order')->findOrFail($entity_id);

        $attributes['invoice_id'] = $invoice->id;
        $attributes['created_by'] = auth()->user()->id;
        $comment = $this->comment->create($attributes);
        return $comment;
    }

    public function update(Comment $comment, array $attributes)
    {
        if (array_key_exists('comment', $attributes) && !empty($attributes['comment'])) {
            $comment->update($attributes);
        }

        return $comment->refresh();
    }
}
