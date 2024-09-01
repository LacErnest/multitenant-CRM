<?php

use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeProjectManagerIdFromEmployeeIdToUserId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['project_manager_id']);
        });

        $this->setProjectManagerAsUser();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->revertProjectManagerAsUser();

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('project_manager_id')->references('id')->on('employees')->onDelete('set null');
        });

    }

    private function setProjectManagerAsUser()
    {
        $projects = Project::all();
        foreach ($projects as $project) {
            if (!is_null($project->project_manager_id)) {
                $pm = Employee::find($project->project_manager_id);
                if ($pm) {
                    $user = User::where([['first_name', $pm->first_name], ['last_name', $pm->last_name], ['company_id', getTenantWithConnection()]])->first();
                    if ($user) {
                        $project->project_manager_id = $user->id;
                    } else {
                        $project->project_manager_id = null;
                    }
                } else {
                    $project->project_manager_id = null;
                }
                $project->save();
            }
        }
    }

    private function revertProjectManagerAsUser()
    {
        $projects = Project::all();
        foreach ($projects as $project) {
            if (!is_null($project->project_manager_id)) {
                $user = User::find($project->project_manager_id);
                if ($user) {
                    $employee = Employee::where([['first_name', $user->first_name], ['last_name', $user->last_name]])->first();
                    if ($employee) {
                        $project->project_manager_id = $employee->id;
                    } else {
                        $project->project_manager_id = null;
                    }
                } else {
                    $project->project_manager_id = null;
                }
                $project->save();
            }
        }
    }
}
