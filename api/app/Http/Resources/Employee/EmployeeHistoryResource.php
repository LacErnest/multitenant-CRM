<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeHistoryResource extends JsonResource
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
          'id'                        => $this->id,
          'employee_id'               => $this->employee_id,
          'working_hours'             => $this->working_hours,
          'start_date'                => $this->start_date,
          'end_date'                  => $this->end_date,
          'created_at'                => $this->created_at,
          'updated_at'                => $this->updated_at,
          'employee_salary'           => $this->employee_salary,
          'default_currency'          => $this->default_currency,
        ];
    }
}
