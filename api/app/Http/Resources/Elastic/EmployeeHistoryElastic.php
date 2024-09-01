<?php


namespace App\Http\Resources\Elastic;


use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeHistoryElastic extends JsonResource
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
              'id'                        => $this->id,
              'employee_id'               => $this->employee_id ?? null,
              'salary'                    => $this->salary ? ceiling($this->salary, 2) : null,
              'working_hours'             => $this->working_hours ?? null,
              'salary_usd'                => $this->salary_usd ? ceiling($this->salary_usd, 2) : null,
              'start_date'                => $this->start_date->timestamp ?? null,
              'end_date'                  => $this->end_date->timestamp ?? null,
              'created_at'                => $this->created_at->timestamp,
              'updated_at'                => $this->updated_at->timestamp ?? null,
              'employee'                  => $this->employee ? $this->employee->name : null,
              'currency_rate'             => $this->currency_rate ?? null,
              'currency_rate_employee'    => $this->currency_rate_employee ?? null,
              'employee_salary'           => $this->employee_salary ?? null,
              'default_currency'          => $this->default_currency ?? null,
          ];
    }
}
