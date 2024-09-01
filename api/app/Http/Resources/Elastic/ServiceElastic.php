<?php

namespace App\Http\Resources\Elastic;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceElastic extends JsonResource
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
          'price' => $this->price,
          'price_unit' => $this->price_unit,
          'resource_id' => $this->resource_id ?? null,
          'resource' => $this->resource_id ? ($this->resourceOfService ? $this->resourceOfService->name : $this->employeeOfService->name) : null,
          'company_id' => getTenantWithConnection(),
          'created_at' => $this->created_at->timestamp,
          'updated_at' => $this->updated_at->timestamp ?? null,
          'deleted_at' => $this->deleted_at->timestamp ?? null
        ];
    }
}
