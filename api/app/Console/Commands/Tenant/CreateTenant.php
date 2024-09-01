<?php

namespace App\Console\Commands\Tenant;

use App\Enums\CurrencyCode;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create_tenant {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new company tenant';

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
        $company_name = $this->argument('name');
        if ($company_name) {
            $currency = $this->ask('What is the currency for the company? Dollar ($) or Euro (€)?');
            while (!($currency == '$' || $currency == '€')) {
                $currency = $this->ask('What is the currency for the company? Dollar ($) or Euro (€)?');
            }
            if ($currency == '€') {
                $currency_code = CurrencyCode::EUR()->getIndex();
            } elseif ($currency == '$') {
                $currency_code = CurrencyCode::USD()->getIndex();
            }

            $acquisition = $this->ask('What is the acquisition date?');
            $earnoutYears = $this->ask('How many years will this company receive earn out?');
            $earnoutBonus = $this->ask('What is the earn out bonus?');
            $gmBonus = $this->ask('What is the gross margin bonus?');

            $validator = Validator::make([
            'What is the acquisition date?' => $acquisition,
            'How many years will this company receive earn out?' => $earnoutYears,
            'What is the earn out bonus?' => $earnoutBonus,
            'What is the gross margin bonus?' => $gmBonus,
            ], [
            'What is the acquisition date?' => ['required', 'date_format:Y-m-d'],
            'How many years will this company receive earn out?' => ['required', 'integer', 'min:0', 'max:99'],
            'What is the earn out bonus?' => ['required', 'numeric', 'min:0', 'max:99'],
            'What is the gross margin bonus?' => ['required', 'numeric', 'min:0', 'max:99'],
            ]);

            if ($validator->fails()) {
                  $this->info('Company not created. See error messages below:');

                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }
                  return 1;
            }

            $tenant = Company::create([
            'name'              => $company_name,
            'currency_code'     => $currency_code,
            'acquisition_date'  => $acquisition,
            'earnout_years'     => $earnoutYears,
            'earnout_bonus'     => $earnoutBonus,
            'gm_bonus'          => $gmBonus,
            ]);

            if ($tenant) {
                  $this->line('Company ' . $company_name . ' created successfully.');
            } else {
                $this->line('Could not create ' . $company_name . '.');
            }
        } else {
            $this->line('You need to specify a name for the company.');
        }
        return 0;
    }
}
