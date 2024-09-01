<?php

namespace App\Http\Resources\Rent;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyRentResource extends JsonResource
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
          'id'            => $this->id,
          'start_date'    => $this->start_date,
          'name'          => $this->name,
          'amount'        => $this->amount,
          'end_date'      => $this->end_date,
          'author_id'     => $this->author_id,
          'author'        => $this->author_id ? $this->author->name : null,
          'created_at'    => $this->created_at,
          'updated_at'    => $this->updated_at,
          'deleted_at'    => $this->deleted_at,
        ];
    }
}
