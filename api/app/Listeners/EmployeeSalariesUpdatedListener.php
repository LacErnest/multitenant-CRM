<?php

namespace App\Listeners;

use App\Events\EmployeeSalariesUpdatedEvent;
use App\Models\Company;
use App\Models\Employee;
use App\Services\ProjectEmployeeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Tenancy\Facades\Tenancy;

class EmployeeSalariesUpdatedListener
{
    private Employee $employee;
    private Company $company;
    private ProjectEmployeeService $projectEmployeeService;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->projectEmployeeService = app(ProjectEmployeeService::class);
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(EmployeeSalariesUpdatedEvent $event)
    {
        $this->employee = $event->getEmployee();
        $this->company = $event->getCompany();
        Tenancy::setTenant($this->company);

        foreach ($this->employee->projects as $project) {
            $this->projectEmployeeService->refresh($this->company, $project);
        }
    }
}
