<?php

namespace App\Jobs;

use App\Models\Comment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Tenancy\Facades\Tenancy;

class CreateProjectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Customer
     */
    private $customer;
    /**
     * @var Company
     */
    private $company;
    /**
     * @var int
     */
    private $budget;
    /**
     * @var Carbon
     */
    private $date;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Customer $customer, Company $company, int $budget, $date = null)
    {
        $this->customer = $customer;
        $this->company = $company;
        $this->budget = $budget;
        $this->date = $date;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Artisan::call('dev:cache_currency_rates');
        Tenancy::setTenant($this->company);
        $date = Carbon::parse($this->company->acquisition_date);
        $newDate = randomDateBetween($date, $date->addYears(mt_rand(0, 3)));

        factory(Project::class)
          ->create([
              'sales_person_id' => $this->customer->sales_person_id,
              'contact_id' => $this->customer->primary_contact_id,
              'created_at' => ($this->date ?: $newDate)->format('Y-m-d H:i:s'),
              'budget' => $this->budget,
          ]);
    }
}
