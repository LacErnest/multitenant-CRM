<?php

namespace App\Http\Resources\Elastic;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyRentElastic extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->syncOriginal();

        return
          [
              'id' => $this->id,
              'name' => $this->name,
              'start_date' => $this->start_date ? $this->start_date->timestamp : null,
              'end_date' => $this->end_date ? $this->end_date->timestamp : null,
              'amount' => $this->amount,
              'admin_amount' => $this->admin_amount,
              'author_id' => $this->author_id,
              'author' => $this->author_id ? $this->author->name : null,
              'created_at' => $this->created_at->timestamp,
              'updated_at' => $this->updated_at->timestamp,
              'deleted_at' => $this->deleted_at->timestamp ?? null
          ];
    }
}
