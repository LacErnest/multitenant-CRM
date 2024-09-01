<?php

namespace App\Http\Resources\XeroEntity;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class XeroEntityStorageResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
          'id'              => $this->id,
          'xero_id'         => $this->xero_id,
          'legal_entity_id' => $this->legal_entity_id,
          'document_id'     => $this->document_id,
          'document_type'   => $this->document_type,
        ];
    }
}
