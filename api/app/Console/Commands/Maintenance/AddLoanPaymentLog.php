<?php

namespace App\Console\Commands\Maintenance;

use App\Enums\CurrencyCode;
use App\Models\Company;
use App\Models\CompanyLoan;
use App\Models\LoanPaymentLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Tenancy\Facades\Tenancy;

class AddLoanPaymentLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:add_loan_payment_log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a loan payment log';

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
        $companyId = $this->ask('What is the id of the company you want to add a payment log?');
        $company = Company::findOrFail($companyId);
        $currencyIsUSD = $company->currency_code == CurrencyCode::USD()->getIndex();
        Tenancy::setTenant($company);

        $loanId = $this->ask('What is the id of the loan you want to add a payment log?');
        $loan = CompanyLoan::findOrFail($loanId);

        $amount = $this->ask('What is the amount for the payment log?');
        $date = $this->ask('What is the date the amount was subtracted from the loan (like 2021-04-20)?');

        $validator = Validator::make([
          'What is the amount for the payment log?' => $amount,
          'What is the date the amount was subtracted from the loan (like 2021-04-20)?' => $date,
        ], [
          'What is the amount for the payment log?' => ['required', 'integer', 'min:1', 'max:99999999'],
          'What is the date the amount was subtracted from the loan (like 2021-04-20)?' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            $this->info('Payment log not created. See error messages below:');

            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $rate = getCurrencyRates()['rates']['USD'];
        $loan->amount_left = $loan->amount_left - $amount;
        $loan->admin_amount_left = $currencyIsUSD ? $loan->amount_left * (1/$rate) : $loan->amount_left;
        $loan->save();

        $log = LoanPaymentLog::create([
          'loan_id' => $loan->id,
          'amount' => $amount,
          'admin_amount' => $currencyIsUSD ? $amount * (1/$rate) : $amount,
          'pay_date' => $date,
        ]);

        if ($log) {
            $this->line('Payment log created successfully.');
        } else {
            $this->line('Failed to create payment log.');
        }

        return 0;
    }
}
