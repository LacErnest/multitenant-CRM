<?php

namespace App\Http\Resources\Loan;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyLoanResource extends JsonResource
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
          'issued_at'     => $this->issued_at,
          'amount'        => $this->amount,
          'paid_at'       => $this->paid_at ?? null,
          'author_id'     => $this->author_id ?? null,
          'author'        => $this->author_id ? $this->author->name : null,
          'created_at'    => $this->created_at ?? null,
          'updated_at'    => $this->updated_at ?? null,
          'deleted_at'    => $this->deleted_at ?? null,
          'description'   => $this->description,
          'amount_left'   => $this->amount_left,
        ];
    }
}
