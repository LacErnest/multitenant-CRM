<?php

namespace App\Console\Commands\Maintenance;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Services\ItemService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Tenancy\Environment;
use Tenancy\Facades\Tenancy;

class RecalculatePrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:recalculate_prices {name? : The name of the company} {entity? : quotes, orders, invoices, purchase_orders} {id? : A specific id of an entity} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate prices for a company, entity, id. Use --all to update everything';

    protected array $entities = ['quotes', 'orders', 'invoices', 'purchase_orders'];

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
        if ($this->option('all')) {
            $companies = Company::all();
            foreach ($companies as $company) {
                $this->recalculatePrices($company);
                $this->line('All prices of Company ' . $company->name . ' updated successfully');
            }
        } else {
            $company_name = $this->argument('name');
            $company = Company::where('name', $company_name)->first();
            if ($company) {
                $entity = $this->argument('entity');
                if ($entity) {
                    if (in_array($entity, $this->entities)) {
                        $id = $this->argument('id');
                        if ($id) {
                              $this->recalculateById($company, $entity, $id);
                        } else {
                            $this->recalculateEntityPrices($company, $entity);
                        }
                    } else {
                        $this->line($entity . ' is not a valid entity. Use quotes, orders, invoices, purchase_orders');
                    }
                } else {
                    $this->recalculatePrices($company);
                    $this->line('All prices of Company ' . $company->name . ' updated successfully');
                }
            } else {
                $this->line('Could not find ' . $this->argument('name') . '.');
            }
        }

        return 0;
    }

    private function recalculatePrices(Company $company): void
    {
        $itemService = App::make(ItemService::class);
        Tenancy::setTenant($company);

        $quotes = Quote::all();
        foreach ($quotes as $quote) {
            $itemService->savePricesAndCurrencyRates($company->id, $quote->id, Quote::class);
        }

        $orders = Order::all();
        foreach ($orders as $order) {
            $itemService->savePricesAndCurrencyRates($company->id, $order->id, Order::class);
        }

        $invoices = Invoice::all();
        foreach ($invoices as $invoice) {
            if ($invoice->legal_entity_id) {
                $itemService->savePricesAndCurrencyRates($company->id, $invoice->id, Invoice::class);
            }
        }

        $purchaseOrders = PurchaseOrder::all();
        foreach ($purchaseOrders as $purchaseOrder) {
            $itemService->savePricesAndCurrencyRates($company->id, $purchaseOrder->id, PurchaseOrder::class);
        }
    }

    private function recalculateEntityPrices(Company $company, string $entity): void
    {
        $itemService = App::make(ItemService::class);
        $model = $this->getModel($entity);

        if ($model) {
            Tenancy::setTenant($company);
            $items = $model::all();
            foreach ($items as $item) {
                if ($item->legal_entity_id) {
                    $itemService->savePricesAndCurrencyRates($company->id, $item->id, $model);
                }
            }
            $this->line('All ' . $entity . ' of Company ' . $company->name . ' updated successfully');
        } else {
            $this->line('Oeps, something went wrong. Sorry');
        }
    }

    private function getModel(string $entity): ?string
    {
        switch ($entity) {
            case 'quotes':
                $model = Quote::class;
                break;
            case 'orders':
                $model = Order::class;
                break;
            case 'invoices':
                $model = Invoice::class;
                break;
            case 'purchase_orders':
                $model = PurchaseOrder::class;
                break;
            default:
                $model = null;
        }

        return $model;
    }

    private function recalculateById(Company $company, string $entity, string $id): void
    {
        $itemService = App::make(ItemService::class);
        $model = $this->getModel($entity);

        if ($model) {
            Tenancy::setTenant($company);
            $item = $model::find($id);
            if ($item) {
                if ($item->legal_entity_id) {
                    $itemService->savePricesAndCurrencyRates($company->id, $item->id, $model);
                    $this->line('Updated ' . $id . ' successfully.');
                }
            } else {
                $this->line('Could not find ' . $id . ' in ' . $entity . '.');
            }
        } else {
            $this->line('Oeps, something went wrong. Sorry');
        }
    }
}
