<?php

namespace App\Observers;

use App\Events\EmployeeSalariesUpdatedEvent;
use App\Jobs\XeroUpdate;
use App\Models\Company;
use App\Models\EmployeeHistory;
use App\Services\Xero\Auth\AuthService as XeroAuthService;
use Exception;

class EmployeeHistoryObserver
{
    public function created(EmployeeHistory $history)
    {
        event(new EmployeeSalariesUpdatedEvent($history->employee));
    }

    public function updated(EmployeeHistory $history)
    {
        if ($history->wasChanged('start_date') || $history->wasChanged('end_date') || $history->wasChanged('salary') || $history->wasChanged('working_hours')) {
            event(new EmployeeSalariesUpdatedEvent($history->employee));
        }
    }

    public function deleted(EmployeeHistory $history)
    {
        event(new EmployeeSalariesUpdatedEvent($history->employee));
    }

    public function saved(EmployeeHistory $history)
    {
        if ($history->wasChanged('start_date') || $history->wasChanged('end_date') || $history->wasChanged('salary') || $history->wasChanged('working_hours')) {
            event(new EmployeeSalariesUpdatedEvent($history->employee));
        }
    }
}
