<?php

namespace App\Http\Resources\LegalEntity;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyLegalEntityResource extends JsonResource
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
          'id'              => $this->pivot->id,
          'company_id'      => $this->pivot->company_id,
          'legal_entity_id' => $this->pivot->legal_entity_id,
          'default'         => $this->pivot->default,
          'created_at'      => $this->pivot->created_at,
          'updated_at'      => $this->pivot->updated_at,
          'name'            => $this->name,
          'country'         => $this->address->country,
          'local'           => $this->pivot->local,
        ];
    }
}
