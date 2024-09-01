<?php

namespace App\Events;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeSalariesUpdatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Employee $employee;
    private Company $company;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Employee $employee)
    {
        $this->employee = $employee;
        $this->company = Company::find(getTenantWithConnection());
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('employee-salaries-updated');
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }
}
