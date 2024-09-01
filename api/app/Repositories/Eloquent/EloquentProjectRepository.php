<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Models\Contact;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EloquentProjectRepository extends EloquentRepository implements ProjectRepositoryInterface
{
    public const CURRENT_MODEL = Project::class;

    /**
     * @var Builder $builder
     */
    protected Builder $builder;

    public function createProjectForQuote(string $name, Contact $contact, array $salesPersonIds, ?array $secondSalesIds = null): Model
    {

        $project = $this->builder()->create([
          'name' => $name,
          'contact_id' => $contact->id,
        ]);

        if ($salesPersonIds) {
            foreach ($salesPersonIds as $salesPersonId) {
                $salesPerson = User::find($salesPersonId);
                if (!$salesPerson) {
                    continue;
                }
                // Update or insert the record in the 'project_sales_persons' table
                if ($salesPerson && !isSalesPersonAttached($salesPersonId, $project->id)) {
                    $salesPerson->saleProjects()->attach($project);
                }
            }
        }

        if ($secondSalesIds) {
            foreach ($secondSalesIds as $leadGenId) {
                $salesPerson = User::find($leadGenId);
                if (!$salesPerson) {
                    continue;
                }
                // Update or insert the record in the 'project_sales_persons' table
                if ($salesPerson && !isLeadGenAttached($leadGenId, $project->id)) {
                    $salesPerson->leadGenProjects()->attach($project);
                }
            }
        }

        return $project;
    }


    private function getNumberOfProjectsForCustomer(array $ids): int
    {
        return $this->builder()->whereIn('contact_id', $ids)->count() + 1;
    }
}
