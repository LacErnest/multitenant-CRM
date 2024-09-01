<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeOfProjectResource extends JsonResource
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
          'project_id' => $this->pivot->project_id,
          'resource_id' => $this->pivot->employee_id,
          'hours' => $this->pivot->hours,
          'created_at' => $this->pivot->created_at,
          'updated_at' => $this->pivot->updated_at
        ];
    }
}
