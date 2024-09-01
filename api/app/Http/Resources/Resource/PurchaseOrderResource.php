<?php

namespace App\Http\Resources\Resource;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
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
          'id' => $this->id,
          'project_id' => $this->project_id,
          'resource' => $this->resource->resource ? $this->resource->resource->name : null,
          'resource_id' => $this->resource_id,
          'date' => $this->date ?? null,
          'delivery_date' => $this->delivery_date ?? null,
          'status' => $this->status ?? null,
          'number' => $this->number ?? null
        ];
    }
}
