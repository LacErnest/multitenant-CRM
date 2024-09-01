<?php

namespace App\Http\Resources\Project;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\ProjectEmployee;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectEmployeesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $euro = $this->euro;
        $months = ProjectEmployee::where([['employee_id', $this->id], ['project_id', $this->project_id]])
          ->orderByDesc('month')->get();
        $months = $months->map(function ($month) use ($euro) {
            $month->employee_cost = $euro ? $month->employee_cost * (1/$month->currency_rate_employee)
              : $month->employee_cost * (1/$month->currency_rate_employee) * $month->currency_rate_dollar;
            return $month;
        });

        return [
          'id' => $this->id,
          'name' => $this->name ?? null,
          'first_name' => $this->first_name,
          'last_name' => $this->last_name,
          'type' => $this->type ?? null,
          'status' => $this->status ?? null,
          'email' => $this->email ?? null,
          'phone_number' => $this->phone_number ?? null,
          'hours' => $this->hours ?? 0,
          'employee_cost' => round($this->employee_cost, 2),
          'is_borrowed' => $this->company_id != getTenantWithConnection(),
          'employee_id' => $this->id,
          'employee' => $this->name ?? null,
          'details' => [
              'columns' => [
                  [
                      'prop' => 'month',
                      'name' => 'month',
                      'type' => 'string',
                  ],
                  [
                      'prop' => 'year',
                      'name' => 'year',
                      'type' => 'integer',
                  ],
                  [
                      'prop' => 'hours',
                      'name' => 'hours',
                      'type' => 'integer',
                  ],
                  [
                      'prop' => 'cost',
                      'name' => 'cost',
                      'type' => 'decimal',
                  ],
              ],
              'rows' => ProjectEmployeeMonthResource::collection($months),
          ],
        ];
    }
}
