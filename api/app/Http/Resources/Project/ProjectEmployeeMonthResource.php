<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectEmployeeMonthResource extends JsonResource
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
          'month' => !($this->month === null) ? date('F', strtotime($this->month)) : null,
          'year' => !($this->month === null) ? date('Y', strtotime($this->month)) : null,
          'hours' => $this->hours,
          'cost' => $this->employee_cost,
        ];
    }
}
