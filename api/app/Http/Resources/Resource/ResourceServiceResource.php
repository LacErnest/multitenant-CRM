<?php

namespace App\Http\Resources\Resource;

use Illuminate\Http\Resources\Json\JsonResource;

class ResourceServiceResource extends JsonResource
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
          'name' => $this->name ?? null,
          'price' => $this->price ?? null,
          'price_unit' => $this->price_unit ?? null,
          'created_at' => $this->created_at ?? null,
          'updated_at' => $this->updated_at ?? null,
        ];
    }
}
