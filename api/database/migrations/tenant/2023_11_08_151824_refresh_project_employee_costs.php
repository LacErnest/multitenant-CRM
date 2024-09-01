<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefreshProjectEmployeeCosts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::beginTransaction();
        try {
            foreach (Project::all() as $project) {
                $project->employee_costs = 0;
                $project->employee_costs_usd = 0;
                $project->external_employee_costs = 0;
                $project->external_employee_costs_usd = 0;
                $project->save();
                $project->refresh();
                foreach ($project->employees as $employee) {
                    $project->refresh();
                    calculateEmployeeCosts(
                        getTenantWithConnection(),
                        $employee,
                        $project,
                        $employee->pivot->hours,
                        $employee->pivot->currency_rate_dollar,
                        $employee->pivot->currency_rate_employee,
                        $employee->pivot->month,
                    );
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
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
