<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Project;
use App\Services\ProjectEmployeeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProjectEmployeesSalaries extends Migration
{
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
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $company = Company::find(getTenantWithConnection());
        foreach(Project::all() as $project) {
            $this->projectEmployeeService->refresh($company, $project);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
