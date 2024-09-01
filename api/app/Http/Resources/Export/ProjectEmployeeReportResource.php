<?php

namespace App\Http\Resources\Export;

use App\Enums\CurrencyCode;
use App\Enums\EmployeeType;
use App\Models\Company;
use App\Models\Project;
use Carbon\Carbon;

class ProjectEmployeeReportResource extends ExtendedJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $company = Company::find(getTenantWithConnection());

        $rate = $this->pivot->currency_rate_dollar ?? 1;

        $symbol = CurrencyCode::make($company->currency_code)->getValue();
        $employeeCost = ($this->pivot->employee_cost ?? 0) * $rate;

        return [
          'VAR_E_FIRST_NAME'          => $this->first_name ?? null,
          'VAR_E_LAST_NAME'          => $this->last_name ?? null,
          'VAR_E_EMAIL'              => $this->email ?? null,
          'VAR_E_PHONE_NUMBER'       => $this->phone_number ?? null,
          'VAR_E_TYPE'               => EmployeeType::make($this->type)->__toString() ?? null,
          'VAR_E_HOURS'              => $this->pivot->hours ?? 0,
          'VAR_E_MONTH'              => Carbon::parse($this->pivot->month)->format('m') ?? 0,
          'VAR_E_YEAR'              => Carbon::parse($this->pivot->month)->format('Y') ?? 0,
          'VAR_E_COST'              => $this->currencyFormatter->formatCurrency($employeeCost, $symbol)
        ];
    }
}
