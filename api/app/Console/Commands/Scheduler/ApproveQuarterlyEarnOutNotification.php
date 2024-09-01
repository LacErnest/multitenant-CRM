<?php

namespace App\Console\Commands\Scheduler;

use App\Enums\UserRole;
use App\Mail\QuarterlyApprovalNotification;
use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ApproveQuarterlyEarnOutNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:approve_earn_out_notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify company owners they can approve the earn out';

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
        $thisQuarter = '';
        $companyOwners = User::where('role', UserRole::owner()->getIndex())->get();
        $companies = Company::all();
        $month = date('n');
        $year = date('Y');
        if ($month == 1) {
            $thisQuarter = 'Q4 ' . ($year - 1);
        } elseif ($month == 4) {
            $thisQuarter = 'Q1 ' . $year;
        } elseif ($month == 7) {
            $thisQuarter = 'Q2 ' . $year;
        } elseif ($month == 10) {
            $thisQuarter = 'Q3 ' . $year;
        }

        $companies->each(function ($company) use ($companyOwners, $thisQuarter) {
            $mailAddresses = [];
            $owners = $companyOwners->where('company_id', $company->id);
            foreach ($owners as $owner) {
                array_push($mailAddresses, $owner->email);
            }

            if (count($mailAddresses)) {
                try {
                    Mail::to($mailAddresses)
                    ->queue(new QuarterlyApprovalNotification($thisQuarter));
                } catch (\Exception $exception) {
                    Log::error($exception);
                }
            }
        });


        return 0;
    }
}
