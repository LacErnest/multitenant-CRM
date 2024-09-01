<?php

namespace App\Http\Resources\Elastic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesCommissionElastic extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->syncOriginal();
        return
          [
              'id'                => $this->id,
              'commission'        => $this->commission,
              'sales_person_id'   => $this->sales_person_id,
              'created_at'        => $this->created_at->timestamp ?? null,
              'updated_at'        => $this->updated_at->timestamp ?? null
          ];
    }
}
