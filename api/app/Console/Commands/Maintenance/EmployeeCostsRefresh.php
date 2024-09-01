<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Company;
use App\Models\Project;
use App\Services\ProjectEmployeeService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;
use Tenancy\Facades\Tenancy;

class EmployeeCostsRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:employees_costs_refresh {name?} {--all} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh projects and employees costs';
    /**
     * @var ProjectEmployeeService
     */
    private $projectEmployeeService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->projectEmployeeService = app(ProjectEmployeeService::class);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->line('Starting refreshing projects and employees costs.');

        if ($this->option('all')) {
            $companies = Company::all();
        } else {
            $company_name = $this->argument('name');
            $company = Company::where('name', $company_name)->first();
            if (empty($company)) {
                $this->line('No company found with that name.');
                return 0;
            }
            $companies = collect([$company]);
        }
        try {
            DB::beginTransaction();
            foreach ($companies as $company) {
                Tenancy::setTenant($company);

                $totalProjects = Project::count();
                $projectsWithNullOrder = Project::doesntHave('order')->count();

                $this->line('Total projects: ' . $totalProjects);
                $this->line('Projects with no order (those will not be included in the calculation): ' . $projectsWithNullOrder);

                $this->line('Starting refreshing projects and employees costs of tenant ' . $company->name . '.');
                foreach (Project::all() as $project) {
                    if (!$project->order) {
                        continue;
                    }
                    $this->projectEmployeeService->refresh($company, $project, $this->option('force'));
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            $this->line('Oeps, something went wrong. Sorry');
            DB::rollBack();
            $this->line('Rolling back to previous database state');
            throw $th;
        }
        $this->line('Success!!!');

        return 0;
    }
}
