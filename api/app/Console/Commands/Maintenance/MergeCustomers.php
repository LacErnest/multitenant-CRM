<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tenancy\Facades\Tenancy;

class MergeCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:merge_customers {customerA : The id of customer 1} {customerB : The id of customer 2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge 2 customers, the second one will be deleted.';

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
        $customerIdA = $this->argument('customerA');
        $customerIdB = $this->argument('customerB');

        $customerA = Customer::with('contacts', 'billing_address', 'operational_address', 'legacyCompanies', 'leadGenerationSales')
          ->findOrFail($customerIdA);
        $customerB = Customer::with('contacts', 'billing_address', 'operational_address', 'legacyCompanies', 'leadGenerationSales')
          ->findOrFail($customerIdB);
        $companies = Company::all();
        $companyOfB = Company::findOrFail($customerB->company_id);
        $deleteOperationalAddress = false;

        DB::beginTransaction();
        try {
            if ($customerB->billing_address) {
                $billingAddress = array_filter($customerB->billing_address->toArray(), function ($key) use ($customerA) {
                    return blank($customerA->billing_address->getAttribute($key));
                }, ARRAY_FILTER_USE_KEY);
                $customerA->billing_address->fill($billingAddress)->save();
            }

            if ($customerB->operational_address) {
                if ($customerA->operational_address) {
                    $operationalAddress = array_filter($customerB->operational_address->toArray(), function ($key) use ($customerA) {
                        return blank($customerA->operational_address->getAttribute($key));
                    }, ARRAY_FILTER_USE_KEY);
                    $customerA->operational_address->fill($operationalAddress)->save();
                    $deleteOperationalAddress = true;
                }
            }

            $customer = array_filter($customerB->toArray(), function ($key) use ($customerA) {
                return blank($customerA->getAttribute($key));
            }, ARRAY_FILTER_USE_KEY);

            if (blank($customerA->sales_person_id)) {
                  $customer['sales_person_id'] = null;
            }

            if (blank($customerA->email)) {
                $customerB->email = null;
                $customerB->save();
            }

            $customerA->fill($customer)->save();

            foreach ($customerB->contacts as $contact) {
                $newContact = $contact;
                $contact->delete();
                $existingContact = Contact::where([['first_name', $newContact->first_name],
                ['last_name', $newContact->last_name], ['customer_id', $customerA->id]])->first();

                if ($existingContact) {
                        $customerContact = array_filter($newContact->toArray(), function ($key) use ($existingContact) {
                            return blank($existingContact->getAttribute($key));
                        }, ARRAY_FILTER_USE_KEY);

                        $existingContact->fill($customerContact)->save();

                    foreach ($companies as $company) {
                          Tenancy::setTenant($company);
                          Project::where('contact_id', $newContact->id)
                          ->update(['contact_id' => $existingContact->id]);
                    }
                } else {
                    $contactCount = $customerA->contacts()->count();
                    $customerContact = $customerA->contacts()->create($newContact->toArray());

                    if ($contactCount == 0) {
                        $customerA->primary_contact_id = $customerContact->id;
                        $customerA->save();
                    }

                    foreach ($companies as $company) {
                        Tenancy::setTenant($company);
                        Project::where('contact_id', $newContact->id)
                        ->update(['contact_id' => $customerContact->id]);
                    }
                }
            }

            $legacyIds = $customerB->legacyCompanies()->pluck('company_id')->toArray();
            if (!empty($legacyIds)) {
                $customerA->legacyCompanies()->syncWithoutDetaching($legacyIds);
                $customerB->legacyCompanies()->detach($legacyIds);
            }

            $customerSales = $customerB->leadGenerationSales;
            foreach ($customerSales as $sale) {
                $customerA->leadGenerationSales()->attach($sale->pivot->sales_person_id, [
                'project_id' => $sale->pivot->project_id,
                'invoice_id' => $sale->pivot->invoice_id,
                'pay_date' => $sale->pivot->pay_date,
                ]);
            }

            Tenancy::setTenant($companyOfB);
            $customerB->leadGenerationSales()->detach();
            $customerB->billing_address()->delete();
            if ($deleteOperationalAddress) {
                $customerB->operational_address()->delete();
            }
            $customerB->delete();

            DB::commit();
            $this->line('Customers merged.');
        } catch (\Throwable $e) {
            $this->line($e->getMessage());
            DB::rollBack();
            $this->line('An error occurred. Rolled back.');
        }

        $this->line('Refreshing elastic documents.');
        Artisan::call('maintenance:elastic_refresh');
        $this->line('Done');

        return 0;
    }
}
