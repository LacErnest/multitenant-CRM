<?php

namespace App\Console\Commands\Maintenance;

use App\Enums\EmployeeType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\Resource;
use App\Models\Service;
use App\Services\ResourceService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tenancy\Facades\Tenancy;

class FreelancerSearchAndDestroy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:search_po_contractors {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search in purchase orders for freelancer that are actually contractors, add name of company for one';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->argument('name')) {
            $company = Company::where('name', $this->argument('name'))->first();
            if ($company) {
                $this->searchFreelancers($company);
                $this->getEmployeesWithoutCountry($company);
            } else {
                $this->line('No company found with that name.');
                return 0;
            }
        } else {
            $companies = Company::all();
            foreach ($companies as $company) {
                $this->searchFreelancers($company);
                $this->getEmployeesWithoutCountry($company);
            }
        }
        $this->line('Refreshing elastic documents.');
        Artisan::call('maintenance:elastic_refresh');

        $this->line('Done');
        return 0;
    }

    private function searchFreelancers($company)
    {
        Tenancy::setTenant($company);
        $resources = Resource::all();

        foreach ($resources as $resource) {
            $employee = null;
            if ($resource->first_name && $resource->last_name) {
                $employee = Employee::where([['first_name', $resource->first_name], ['last_name', $resource->last_name]])->first();
            }
            if (!$employee) {
                $employee = Employee::where(DB::raw("CONCAT_WS(' ',last_name,first_name)"), $resource->name)->first();
                if (!$employee) {
                    $employee = Employee::where(DB::raw("CONCAT_WS(' ',first_name,last_name)"), $resource->name)->first();
                }
            }

            if ($employee) {
                $resourceName = $resource->name;
                $deleteResource = false;
                $deleteAddress = false;
                DB::beginTransaction();
                try {
                    PurchaseOrder::where('resource_id', $resource->id)->update([
                    'resource_id' => $employee->id
                    ]);
                    Service::where('resource_id', $resource->id)->update([
                      'resource_id' => $employee->id
                    ]);
                    if (!$employee->address) {
                              $employee->address_id = $resource->address_id;
                              $employee->load('address')->save();
                    } else {
                        if (!$employee->address->country) {
                            $employee->address->country = $resource->address->country;
                            $employee->address->save();
                        }
                        $deleteAddress = true;
                    }

                    DB::commit();
                    $deleteResource = true;
                    $this->line('Purchase order of ' . $resourceName . ' transferred to employee ' . $employee->name . '.');
                } catch (\Throwable $e) {
                    $this->line($e->getMessage());
                    DB::rollBack();
                    $this->line('An error occurred when transferring. Rolled back for ' . $resourceName . '.');
                }

                DB::beginTransaction();
                try {
                    if ($deleteResource) {
                        if ($deleteAddress) {
                              $resource->address()->delete();
                        }
                        $resource->delete();
                        DB::commit();
                        $this->line('Deleted resource ' . $resourceName . '.');
                    }
                } catch (\Throwable $e) {
                    $this->line($e->getMessage());
                    DB::rollBack();
                    $this->line('An error occurred deleting ' . $resourceName . '. Rolled back.');
                }
            }
        }
    }

    private function getEmployeesWithoutCountry($company)
    {
        Tenancy::setTenant($company);
        $this->line('Contractors without a country set for company ' . $company->name);
        $employeesWithoutCountry = Employee::with('address')->where('type', EmployeeType::contractor()->getIndex())->get();
        if ($employeesWithoutCountry->isNotEmpty()) {
            foreach ($employeesWithoutCountry as $item) {
                if (!$item->address || !$item->address->country) {
                    $this->line($item->id . ' => ' . $item->name);
                }
            }
        }
        $this->line('');
    }
}
