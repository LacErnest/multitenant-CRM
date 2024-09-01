<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\OrderService;
use Illuminate\Support\Facades\App;

class ProjectObserver
{
    public function updated(Project $project)
    {
        if ($project->isDirty('employee_costs')) {
            if ($project->employee_costs > $project->getOriginal('employee_costs')) {
                $orderService = App::make(OrderService::class);
                $companyId = getTenantWithConnection();
                $orderService->markUpCheck($project, $companyId);
            }
            shareOrderGrossMargin($project->order);
        }
    }
}
