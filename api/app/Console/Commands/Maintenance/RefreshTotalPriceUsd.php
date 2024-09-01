<?php

namespace App\Console\Commands\Maintenance;

use App\Enums\CurrencyCode;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Quote;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Str;
use Tenancy\Facades\Tenancy;

class RefreshTotalPriceUsd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:refresh_total_price_usd {entity} {--company=} {--ids=} {--manual-input}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Invoice, Quote, Order total price usd.';

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
        $model_name = $this->getModelName();
        $companies = $this->getCompanies();

        if ($model_name && ($model_name == 'Quote' || $model_name == 'Invoice' || $model_name == 'Order')) {
            $model = "App\Models\ $model_name";
            $model = str_replace(' ', '', $model);
            foreach ($companies as $company) {
                Tenancy::setTenant($company);
                $entities = $this->getEntities($model);
                foreach ($entities as $entity) {
                    if ($entity) {
                        $this->refresh($company, $entity);
                    }
                }
                $this->line('Refreshing done for company ' . $company->name . ' successfully');
            }
        } else {
            $this->line('Invalid entity. It must be Invoice, Quote or Order');
        }
        return 0;
    }

    /**
     * Get allowed companies
     * @return Collection
     */
    private function getCompanies()
    {
        $company_name = $this->option('company');
        $company = Company::where('name', $company_name)->first();
        if ($company) {
            $companies = collect([$company]);
        } else {
            $companies = Company::all();
        }
        return $companies;
    }

    /**
     * Get given parameter model name
     * @return  string | null
     */
    private function getModelName()
    {
        $model_name = $this->argument('entity');
        if ($model_name) {
            $model_name = Str::camel($model_name);
            $model_name = ucfirst($model_name);
            return $model_name;
        }
        return null;
    }

    /**
     * Set total price and total price usd for the given entity
     * @return void
     */
    private function refresh($company, $model)
    {
        $isUSDCurrency = $company->currency_code === CurrencyCode::USD()->getIndex();
        if ($model->manual_input) {
            $manual_price = entityPrice(Invoice::class, $model->id);
            $total_price = $manual_price * (!$isUSDCurrency ?
            safeDivide(1, $model->currency_rate_customer) : $model->currency_rate_company * safeDivide(1, $model->currency_rate_customer));
            $total_price_usd = $total_price * ($isUSDCurrency ?
            safeDivide(1, $model->currency_rate_company) : $model->currency_rate_company);
            $model->total_price = $total_price;
            $model->total_price_usd = $total_price_usd;
            $model->save();
        } elseif (!$this->option('manual-input')) {
            if ($isUSDCurrency) {
                $total_price_usd = entityPrice(Invoice::class, $model->id);
                $total_price = $total_price_usd * $model->currency_rate_company;
                $manual_price = 0;
            } else {
                $total_price = entityPrice(Invoice::class, $model->id);
                $total_price_usd = $total_price * $model->currency_rate_company;
                $manual_price = 0;
            }
            $model->total_price = $total_price;
            $model->total_price_usd = $total_price_usd;
            $model->save();
        }
    }

    /**
     * Find entities items by ids parametes
     * @return Collection
     */
    private function getEntities($model)
    {
        $model_ids = $this->option('ids');
        if (!empty($model_ids)) {
            $ids = explode(',', $model_ids);
            $entities = $model::whereIn('id', $ids)->get();
        } else {
            $entities = $model::all();
        }
        return $entities;
    }
}
