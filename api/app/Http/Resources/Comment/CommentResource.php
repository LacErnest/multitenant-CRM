<?php

namespace App\Http\Resources\Comment;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
          'id'                     => $this->id,
          'creator'             => [
              'id' => $this->creator->id,
              'name' => $this->creator->name,
          ],
          'content'                => $this->content,
          'project_id'             => $this->project_id ?? null,
          'created_at'             => $this->created_at,
          'updated_at'             => $this->updated_at,
        ];
    }
}
