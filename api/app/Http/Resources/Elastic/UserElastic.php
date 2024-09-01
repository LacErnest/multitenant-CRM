<?php

namespace App\Http\Resources\Elastic;

use Illuminate\Http\Resources\Json\JsonResource;

class UserElastic extends JsonResource
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
              'first_name' => $this->first_name ?? null,
              'last_name' => $this->last_name ?? null,
              'name' => $this->name ?? null,
              'email' => $this->email ?? null,
              'company_id' => $this->company_id ?? null,
              'company' => $this->company ? $this->company->name : null,
              'role' => $this->role ?? null,
              'created_at' => $this->created_at->timestamp,
              'updated_at' => $this->updated_at->timestamp ?? null,
              'disabled_at' => $this->disabled_at->timestamp ?? null,
          ];
    }
}
