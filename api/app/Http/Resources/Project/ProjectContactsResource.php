<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectContactsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return
          [
              'id' => $this->id,
              'first_name'=> $this->first_name,
              'last_name' => $this->last_name,
              'email' => $this->email,
              'phone_number' => $this->phone_number
          ];
    }
}
