<?php

namespace App\Console\Commands\Maintenance;

use App\Contracts\Repositories\GlobalTaxRateRepositoryInterface;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Services\ItemService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Tenancy\Environment;

class UpdatePricesOnProduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:update_manual_prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update manual prices on production';

    /**
     * Create a new command instance.
     *
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
        $itemService = App::make(ItemService::class);
        $companies = Company::all();
        foreach ($companies as $company) {
            $environment = app(Environment::class);
            $environment->setTenant($company);

            $quotes = Quote::where('manual_input', true)->get();
            foreach ($quotes as $quote) {
                $itemService->savePricesAndCurrencyRates($company->id, $quote->id, Quote::class);
            }

            $orders = Order::where('manual_input', true)->get();
            foreach ($orders as $order) {
                $itemService->savePricesAndCurrencyRates($company->id, $order->id, Order::class);
            }

            $invoices = Invoice::where('manual_input', true)->get();
            foreach ($invoices as $invoice) {
                if ($invoice->legal_entity_id) {
                    $itemService->savePricesAndCurrencyRates($company->id, $invoice->id, Invoice::class);
                }
            }

            $purchaseOrders = PurchaseOrder::where('manual_input', true)->get();
            foreach ($purchaseOrders as $purchaseOrder) {
                $itemService->savePricesAndCurrencyRates($company->id, $purchaseOrder->id, PurchaseOrder::class);
            }
        }

        return 0;
    }
}
