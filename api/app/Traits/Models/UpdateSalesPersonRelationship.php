<?php

namespace App\Traits\Models;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Tenancy\Facades\Tenancy;

trait UpdateSalesPersonRelationship
{
    public function updateSalesPersonRelationship()
    {
        $companies = Company::all();
        foreach ($companies as $company) {
            Tenancy::setTenant($company);
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
        }
    }

    public function updateCompanySalesPersonRelationship()
    {
        $projects = Project::with('quotes')->get();
        foreach ($projects as $project) {
            $salesPersonId = $project->sales_person_id;
            $salesPerson = User::find($salesPersonId);
            $leadGenId = $project->second_sales_person_id;
            $leadGen = User::find($leadGenId);

            $projectQuotes = $project->quotes;

            // Add sales person to attach list (if present) and ensure uniqueness
            if ($salesPerson && !isSalesPersonAttached($salesPersonId, $project->id)) {
                $salesPerson->saleProjects()->attach($project);
            }

            if ($leadGen && !isLeadGenAttached($leadGenId, $project->id)) {
                $leadGen->leadGenProjects()->attach($project);
            }
            if (!empty($salesPerson)) {
                $salesPerson->quotes()->attach($projectQuotes);
            }
        }
    }
}
