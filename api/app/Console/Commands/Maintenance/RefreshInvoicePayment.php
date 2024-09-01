<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Company;
use App\Models\Invoice;
use App\Services\InvoicePaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tenancy\Environment;
use Tenancy\Facades\Tenancy;

class RefreshInvoicePayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:refresh_invoice_payment {name?} {--all} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate paid invoice amount for a company. Use --all to update everything and --force to continue running process anyway';


    /**
     * @var InvoicePaymentService
     */
    protected $invoicePaymentService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->invoicePaymentService = app(InvoicePaymentService::class);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('all')) {
            $companies = Company::all();
        } else {
            $company_name = $this->argument('name');
            $company = Company::where('name', $company_name)->first();
            if (empty($company)) {
                $this->line('Company ' . $company_name . ' not found');
                return 0;
            }
            $companies = collect([$company]);
        }
        DB::beginTransaction();
        try {
            foreach ($companies as $company) {
                $this->recalculateInvoicePayment($company);
                $this->line('Company ' . $company->name . ' invoices updated successfully');
            }
            DB::commit();
        } catch (\Throwable $th) {
            $this->line('Something went wrong!!! Rolling back to previous database state.');
            DB::rollBack();
            throw $th;
        }
        return 0;
    }

    /**
     * Recalculates all invoice payments for a company
     * @param Company $company
     * @return void
     * @throws \Throwable
     */
    private function recalculateInvoicePayment(Company $company): void
    {
        Tenancy::setTenant($company);
        foreach (Invoice::all() as $invoice) {
            try {
                $this->invoicePaymentService->refreshInvoicedPaymentsAmountForSingleInvoice($invoice);
            } catch (\Throwable $th) {
                if (!$this->option('force')) {
                    throw $th;
                }
            }
        }
    }
}
