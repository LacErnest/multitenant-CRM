<?php

namespace App\Console\Commands\Scheduler;

use App\Enums\CustomerStatus;
use App\Enums\UserRole;
use App\Mail\NoQuotePotentialCustomer;
use App\Mail\SixMonthsNoQuote;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Tenancy\Facades\Tenancy;

class MonthlyQuoteNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:monthly_quote_notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send mails when customers have no quotes';

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
        foreach (Customer::with('contacts')->get() as $customer) {
            $ccMail = [];
            $salesperson = User::where('id', $customer->sales_person_id)->first();
            if ($customer->company_id) {
                Tenancy::setTenant(Company::find($customer->company_id));
                $owners = User::with('mail_preference')->where([['company_id', $customer->company_id], ['role', UserRole::owner()->getIndex()]])->get();
                foreach ($owners as $owner) {
                    $mail = $owner->mail_preference;
                    if ($mail && $mail->quotes == 1) {
                        array_push($ccMail, $owner->email);
                    }
                }
            } elseif ($salesperson) {
                Tenancy::setTenant(Company::find($salesperson->company_id));
            } else {
                continue;
            }

            if (CustomerStatus::isPotential($customer->status) && $customer->created_at < Carbon::now()->subMonth()) {
                Mail::to($salesperson->email)
                ->cc($ccMail)
                ->queue(new NoQuotePotentialCustomer($customer->id, $customer->primary_contact_id));
            } elseif (CustomerStatus::isActive($customer->status)) {
                $quotes = $customer->contacts->map(function ($contact) {
                    return $contact->quotes->sortByDesc('created_at')->first();
                });
                $lastQuote = $quotes ? $quotes->sortByDesc('created_at')->first() : null;
                if ($lastQuote) {
                    if ($lastQuote->created_at < Carbon::now()->subMonths(6)) {
                        Mail::to($salesperson->email)
                        ->cc($ccMail)
                        ->queue(new SixMonthsNoQuote($customer->id, $customer->primary_contact_id, $lastQuote->created_at));
                    }
                    if ($lastQuote->created_at < Carbon::now()->subyear()) {
                        $customer->status = CustomerStatus::inactive()->getIndex();
                        $customer->save();
                    }
                }
            }
        }
          return 0;
    }
}
