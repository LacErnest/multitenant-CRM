<?php

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Tenancy\Facades\Tenancy;

class UpdateProjectQuoteSalesPersonData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            $projects = Project::with('quotes')->get();

            foreach ($projects as $project) {
                $salesPersonId = $project->sales_person_id;
                $salesPerson = User::find($salesPersonId);
                $leadGenId = $project->second_sales_person_id;
                $leadGen = User::find($leadGenId);

                $projectQuotes = $project->quotes;

                if (!empty($salesPerson) && !isSalesPersonAttached($salesPersonId, $project->id)) {
                    $salesPerson->saleProjects()->attach($project);
                }

                if (!empty($leadGen) && !isLeadGenAttached($leadGenId, $project->id)) {
                    $leadGen->leadGenProjects()->attach($project);
                }
                if (!empty($salesPerson)) {
                    $salesPerson->quotes()->attach($projectQuotes);
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
            dd($th);
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
