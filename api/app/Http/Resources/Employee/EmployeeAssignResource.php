<?php

namespace App\Http\Resources\Employee;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectEmployee;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class EmployeeAssignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $projects = ProjectEmployee::with('project.order')
          ->where('employee_id', $this->employee_id)
          ->whereMonth('month', $request->input('month'))
          ->whereYear('month', $request->input('year'))->get();
        $assignedHours = $projects->sum('hours');

        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $projectsIds = Project::where('project_manager_id', auth()->user()->id)->pluck('id')->toArray();
            $projects = $projects->whereIn('project_id', $projectsIds);
        }

        $rates = getCurrencyRates();
        $employeeCurrency = CurrencyCode::make((int)$this->employee->default_currency)->__toString();
        $employeeRate = $rates['rates'][$employeeCurrency];

        if (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex()) {
            $price = 'total_price';
            $rate = safeDivide(1, $employeeRate);
        } else {
            $price = 'total_price_usd';
            $rate = safeDivide(1, $employeeRate) * $rates['rates']['USD'];
        }
        $hourlyWage = safeDivide($this->employee->salary, $this->employee->working_hours) * $rate;

        if ($projects->isNotEmpty()) {
            $projects = $projects->map(function ($project) use ($hourlyWage) {
                $project->costs = $hourlyWage * $project->hours;
                return $project;
            });
        }

        return [
          'id'                => $this->employee_id,
          'name'              => $this->employee->name,
          'role'              => $this->employee->role,
          'working_hours'     => $this->employee->working_hours,
          'hourly_wage'       => $hourlyWage,
          'already_assigned'  => $assignedHours,
          'details' => [
              'columns' => [
                  [
                      'prop' => 'order',
                      'name' => 'order',
                      'type' => 'string',
                  ],
                  [
                      'prop' => 'hours',
                      'name' => 'assigned hours',
                      'type' => 'integer',
                  ],
                  [
                      'prop' => 'costs',
                      'name' => 'costs',
                      'type' => 'decimal',
                  ],
                  [
                      'prop' => 'customer',
                      'name' => 'customer',
                      'type' => 'string',
                  ],
                  [
                      'prop' => $price,
                      'name' => 'price',
                      'type' => 'decimal',
                  ],
              ],
              'rows' => EmployeeProjectResource::collection($projects),
          ],
        ];
    }
}
