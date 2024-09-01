<?php

namespace App\Console\Commands\Scheduler;

use App\Enums\CustomerStatus;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Jobs\EmailUpdate;
use App\Mail\NoQuotePotentialCustomer;
use App\Mail\SixMonthsNoQuote;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Quote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Tenancy\Facades\Tenancy;

class QuoteNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:quote_notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify sales persons for quotes';

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
        $companies = Company::all();
        $companies->each(function ($company) {
            Tenancy::setTenant($company);

            $fiveDaysAgo = now()->subDays(5)->setTime(0, 0, 0);
            $expiredQuotes = Quote::whereDate('expiry_date', '<=', $fiveDaysAgo->toDateTimeString())
              ->where('status', QuoteStatus::sent()->getIndex())
              ->where('shadow', false)
              ->get();

            foreach ($expiredQuotes as $quote) {
                if (($quote->email_sent_date === null) || (Carbon::parse($quote->email_sent_date) <= $fiveDaysAgo)) {
                    $quote->email_sent_date = now();
                    $quote->save();
                    EmailUpdate::dispatch($company->id, Quote::class, $quote->id)->onQueue('emails');
                }
            }
        });

        return 0;
    }
}
